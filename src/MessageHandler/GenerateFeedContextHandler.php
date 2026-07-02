<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MessageHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Event\FeedPublishBlockedEvent;
use Setono\SyliusFeedPlugin\Generator\FeedContextResultRecorderInterface;
use Setono\SyliusFeedPlugin\Generator\FeedGeneratorInterface;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeedContext;
use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Publish\PublishGateInterface;
use Setono\SyliusFeedPlugin\Repository\FeedContextResultRepositoryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;

/**
 * Generates one context's feed file to temporary (staging) storage, records the candidate result,
 * then runs it through the publish gate (§6.6) to decide whether it may be promoted to the live feed
 * (the promotion itself happens per-context on `complete`). Finally it counts the context as
 * completed; the handler that finishes the last context completes the feed (§6.3). Because the
 * generator clears the entity manager while streaming, the bookkeeping re-fetches the feed.
 */
final class GenerateFeedContextHandler
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly FeedRepositoryInterface $feedRepository,
        private readonly RepositoryInterface $channelRepository,
        private readonly FeedGeneratorInterface $feedGenerator,
        private readonly FeedContextResultRecorderInterface $feedContextResultRecorder,
        private readonly FeedContextResultRepositoryInterface $feedContextResultRepository,
        private readonly PublishGateInterface $publishGate,
        private readonly EventDispatcherInterface $eventDispatcher,
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

        $context = $this->buildContext($message);

        try {
            $result = $this->feedGenerator->generate($feed, $context);
        } catch (\Throwable $exception) {
            $this->fail($message->feed);

            throw $exception;
        }

        // The generator clears the entity manager while streaming, so re-fetch a managed feed; it
        // was loaded moments ago, so it is guaranteed to still exist.
        $feed = $this->feedRepository->find($message->feed);
        Assert::isInstanceOf($feed, FeedInterface::class);

        // Record the excluded-item report + size/count for this context now that generation
        // finished, against the managed feed (§11). The candidate starts out `pending`.
        $contextKey = $context->key();
        $candidate = $this->feedContextResultRecorder->record($feed, $contextKey, $result);

        $this->gate($feed, $contextKey, $candidate);

        $completed = $this->feedRepository->incrementCompletedContexts($feed);
        if (null !== $feed->getContextCount() && $completed >= $feed->getContextCount()) {
            $this->transition($feed, FeedGraph::TRANSITION_COMPLETE);
        }
    }

    /**
     * Runs the freshly recorded candidate through the publish gate against the last published
     * baseline (§6.6), stamps the outcome onto the candidate, and notifies listeners when the gate
     * blocked promotion or a `warn` guardrail flagged a concern.
     */
    private function gate(FeedInterface $feed, string $contextKey, FeedContextResultInterface $candidate): void
    {
        $baseline = $this->feedContextResultRepository->findLatestPublished($feed, $contextKey);

        $guardrails = $feed->getPublishConfig()['guardrails'] ?? [];
        $decision = $this->publishGate->evaluate($candidate, $baseline, is_array($guardrails) ? $guardrails : []);

        $candidate->setPublishState(
            $decision->blocked
                ? FeedContextResultInterface::PUBLISH_STATE_BLOCKED
                : FeedContextResultInterface::PUBLISH_STATE_PUBLISHED,
        );
        $candidate->setPublishCheck([] === $decision->reasons ? null : $decision->reasons);
        $this->getManager($candidate)->flush();

        if ($decision->blocked || [] !== $decision->reasons) {
            $this->eventDispatcher->dispatch(
                new FeedPublishBlockedEvent($feed, $contextKey, $decision->reasons, $decision->blocked),
            );
        }
    }

    private function buildContext(GenerateFeedContext $message): FeedContext
    {
        $channel = null;
        if (null !== $message->channel) {
            $candidate = $this->channelRepository->findOneBy(['code' => $message->channel]);
            $channel = $candidate instanceof ChannelInterface ? $candidate : null;
        }

        return new FeedContext($channel, $message->locale, $message->currency);
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
