<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Handler;

use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeed;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Validator\TemplateValidatorInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Registry;

final class ProcessFeedHandler implements MessageHandlerInterface
{
    use GetFeedTrait;

    private ObjectManager $feedManager;

    private FeedTypeRegistryInterface $feedTypeRegistry;

    private MessageBusInterface $commandBus;

    private Registry $workflowRegistry;

    private TemplateValidatorInterface $templateValidator;

    public function __construct(
        FeedRepositoryInterface $feedRepository,
        ObjectManager $feedManager,
        FeedTypeRegistryInterface $feedTypeRegistry,
        MessageBusInterface $commandBus,
        Registry $workflowRegistry,
        TemplateValidatorInterface $templateValidator
    ) {
        $this->feedRepository = $feedRepository;
        $this->feedManager = $feedManager;
        $this->feedTypeRegistry = $feedTypeRegistry;
        $this->commandBus = $commandBus;
        $this->workflowRegistry = $workflowRegistry;
        $this->templateValidator = $templateValidator;
    }

    public function __invoke(ProcessFeed $message): void
    {
        $feed = $this->getFeed($message->getFeedId());

        $feedType = $this->feedTypeRegistry->get((string) $feed->getFeedType());

        $this->templateValidator->validate($feedType->getTemplate());

        $this->applyProcessTransition($feed);

        $this->feedManager->flush();

        /** @var ChannelInterface $channel */
        foreach ($feed->getChannels() as $channel) {
            foreach ($channel->getLocales() as $locale) {
                $this->commandBus->dispatch(new GenerateFeed((int) $feed->getId(), $channel, $locale));
            }
        }
    }

    private function applyProcessTransition(FeedInterface $feed): void
    {
        try {
            $workflow = $this->workflowRegistry->get($feed, FeedGraph::GRAPH);
        } catch (InvalidArgumentException $e) {
            throw new UnrecoverableMessageHandlingException('An error occurred when trying to get the workflow for the feed', 0, $e);
        }

        if (!$workflow->can($feed, FeedGraph::TRANSITION_PROCESS)) {
            throw new InvalidArgumentException(sprintf('The feed is not in a valid state. State is %s', $feed->getState()));
        }

        $workflow->apply($feed, FeedGraph::TRANSITION_PROCESS);
    }
}
