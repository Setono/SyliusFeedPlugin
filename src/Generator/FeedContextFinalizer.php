<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Event\FeedPublishBlockedEvent;
use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Publish\PublishGateInterface;
use Setono\SyliusFeedPlugin\Repository\FeedContextResultRepositoryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Symfony\Component\Workflow\Registry;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Records a context's candidate result, runs the publish gate (§6.6), counts the context as completed
 * and completes the feed when the last context finishes (§6.3). Shared by the inline
 * {@see \Setono\SyliusFeedPlugin\MessageHandler\GenerateFeedContextHandler} and the fan-out
 * {@see \Setono\SyliusFeedPlugin\MessageHandler\FinalizeFeedContextHandler}, so both paths finalize
 * identically. The counter increment is atomic, so exactly one context observes the value that
 * reaches the total and completes the feed.
 */
final class FeedContextFinalizer implements FeedContextFinalizerInterface
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly FeedRepositoryInterface $feedRepository,
        private readonly FeedContextResultRecorderInterface $feedContextResultRecorder,
        private readonly FeedContextResultRepositoryInterface $feedContextResultRepository,
        private readonly PublishGateInterface $publishGate,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Registry $workflowRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function finalize(FeedInterface $feed, FeedContext $context, GenerationResult $result): void
    {
        // Record the excluded-item report + size/count for this context now that generation finished,
        // against the managed feed (§11). The candidate starts out `pending`.
        $contextKey = $context->key();
        $candidate = $this->feedContextResultRecorder->record($feed, $contextKey, $result);

        // Stamp the context's dimension codes so delivery targets can be matched during finalize (§12)
        // without needing to reconstruct the channel entity.
        $candidate->setChannelCode($context->getChannel()?->getCode());
        $candidate->setLocaleCode($context->getLocale());
        $candidate->setCurrencyCode($context->getCurrencyCode());

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

    private function transition(FeedInterface $feed, string $transition): void
    {
        $workflow = $this->workflowRegistry->get($feed, FeedGraph::GRAPH);
        if ($workflow->can($feed, $transition)) {
            $workflow->apply($feed, $transition);
            $this->getManager($feed)->flush();
        }
    }
}
