<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MessageHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\Context\ContextFactoryInterface;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeedContext;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Registry;

/**
 * Starts a feed's generation run (§6.3): applies the `process` transition, records the number of
 * contexts to generate, and fans out one {@see GenerateFeedContext} per context. A feed with no
 * contexts completes immediately.
 */
final class ProcessFeedHandler
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly RepositoryInterface $feedRepository,
        private readonly ContextFactoryInterface $contextFactory,
        private readonly MessageBusInterface $commandBus,
        private readonly Registry $workflowRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(ProcessFeed $message): void
    {
        $feed = $this->feedRepository->find($message->feed);
        if (!$feed instanceof FeedInterface) {
            return;
        }

        $workflow = $this->workflowRegistry->get($feed, FeedGraph::GRAPH);
        if (!$workflow->can($feed, FeedGraph::TRANSITION_PROCESS)) {
            return;
        }

        $contexts = $this->contextFactory->create($feed);

        $feed->setContextCount(count($contexts));
        $feed->setCompletedContextCount(0);
        $workflow->apply($feed, FeedGraph::TRANSITION_PROCESS);
        $this->getManager($feed)->flush();

        if ([] === $contexts) {
            if ($workflow->can($feed, FeedGraph::TRANSITION_COMPLETE)) {
                $workflow->apply($feed, FeedGraph::TRANSITION_COMPLETE);
                $this->getManager($feed)->flush();
            }

            return;
        }

        foreach ($contexts as $context) {
            $this->commandBus->dispatch(new GenerateFeedContext(
                $feed,
                $context->getChannel()?->getCode(),
                $context->getLocale(),
                $context->getCurrencyCode(),
            ));
        }
    }
}
