<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use InvalidArgumentException;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeedChunk;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Validator\TemplateValidatorInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Registry;

final class ProcessFeedHandler implements MessageHandlerInterface
{
    /** @var FeedRepositoryInterface */
    private $feedRepository;

    /** @var ObjectManager */
    private $feedManager;

    /** @var FeedTypeRegistryInterface */
    private $feedTypeRegistry;

    /** @var MessageBusInterface */
    private $commandBus;

    /** @var Registry */
    private $workflowRegistry;

    /** @var TemplateValidatorInterface */
    private $templateValidator;

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

    /**
     * @throws StringsException
     */
    public function __invoke(ProcessFeed $message): void
    {
        $feed = $this->getFeed($message->getFeedId());

        $feedType = $this->feedTypeRegistry->get($feed->getFeedType());

        $this->templateValidator->validate($feedType->getTemplate());

        $this->applyProcessTransition($feed);

        $this->feedManager->flush();

        $dataProvider = $feedType->getDataProvider();
        foreach ($dataProvider->getBatches() as $batch) {
            $this->commandBus->dispatch(new GenerateFeedChunk($feed->getId(), $batch));
        }
    }

    private function getFeed(int $feedId): FeedInterface
    {
        /** @var FeedInterface|null $feed */
        $feed = $this->feedRepository->find($feedId);

        if (null === $feed) {
            throw new UnrecoverableMessageHandlingException('Feed does not exist');
        }

        return $feed;
    }

    /**
     * @throws StringsException
     */
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
