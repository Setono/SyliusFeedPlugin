<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MessageHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Generator\FeedGeneratorInterface;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeedContext;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Workflow\Registry;
use Webmozart\Assert\Assert;

/**
 * Generates one context's feed file to temporary storage, then atomically counts it as completed;
 * the handler that finishes the last context completes the feed (§6.3). Because the generator
 * clears the entity manager while streaming, the completion bookkeeping re-fetches the feed.
 */
final class GenerateFeedContextHandler
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly FeedRepositoryInterface $feedRepository,
        private readonly RepositoryInterface $channelRepository,
        private readonly FeedGeneratorInterface $feedGenerator,
        private readonly Registry $workflowRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(GenerateFeedContext $message): void
    {
        $feed = $this->feedRepository->find($message->feed);
        if (!$feed instanceof FeedInterface) {
            return;
        }

        try {
            $this->feedGenerator->generate($feed, $this->buildContext($message));
        } catch (\Throwable $exception) {
            $this->fail($message->feed);

            throw $exception;
        }

        // The generator clears the entity manager while streaming, so re-fetch a managed feed; it
        // was loaded moments ago, so it is guaranteed to still exist.
        $feed = $this->feedRepository->find($message->feed);
        Assert::isInstanceOf($feed, FeedInterface::class);

        $completed = $this->feedRepository->incrementCompletedContexts($feed);
        if (null !== $feed->getContextCount() && $completed >= $feed->getContextCount()) {
            $this->transition($feed, FeedGraph::TRANSITION_COMPLETE);
        }
    }

    private function buildContext(GenerateFeedContext $message): FeedContext
    {
        $channel = null;
        if (null !== $message->channelCode) {
            $candidate = $this->channelRepository->findOneBy(['code' => $message->channelCode]);
            $channel = $candidate instanceof ChannelInterface ? $candidate : null;
        }

        return new FeedContext($channel, $message->locale, $message->currencyCode);
    }

    private function fail(int $feedId): void
    {
        $feed = $this->feedRepository->find($feedId);
        if ($feed instanceof FeedInterface) {
            $this->transition($feed, FeedGraph::TRANSITION_FAIL);
        }
    }

    private function transition(FeedInterface $feed, string $transition): void
    {
        $workflow = $this->workflowRegistry->get($feed, FeedGraph::GRAPH);
        if ($workflow->can($feed, $transition)) {
            $workflow->apply($feed, $transition);
            $this->getManager($feed)->flush();
        }
    }
}
