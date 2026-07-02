<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventSubscriber;

use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Delivery\DeliveryServiceInterface;
use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Repository\FeedContextResultRepositoryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

/**
 * On `complete`, promotes each freshly generated context file from staging to canonical storage —
 * but only per-context and only when the publish gate let it through (§6.6). A context whose latest
 * result is `blocked` keeps its previously published canonical file live and its candidate is
 * retained in staging for inspection (and a possible "publish anyway"); every other context is
 * copied across (overwriting) and its staging copy removed. The canonical directory is never wiped
 * wholesale, so a blocked context never loses its live file.
 *
 * Once every file is promoted, each *published* context's canonical file set is pushed to its
 * matching delivery targets (§12). A blocked context is never delivered.
 */
final class MoveGeneratedFeedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly FilesystemOperator $feedTmpFilesystem,
        private readonly FilesystemOperator $feedFilesystem,
        private readonly FeedContextResultRepositoryInterface $feedContextResultRepository,
        private readonly DeliveryServiceInterface $deliveryService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            sprintf('workflow.%s.completed.%s', FeedGraph::GRAPH, FeedGraph::TRANSITION_COMPLETE) => 'onCompleted',
        ];
    }

    public function onCompleted(CompletedEvent $event): void
    {
        $feed = $event->getSubject();
        if (!$feed instanceof FeedInterface) {
            return;
        }

        $directory = (string) $feed->getCode();

        /** @var array<string, FeedContextResultInterface> $publishedContexts */
        $publishedContexts = [];

        foreach ($this->feedTmpFilesystem->listContents($directory, true) as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $path = $item->path();
            $contextKey = pathinfo($path, \PATHINFO_FILENAME);

            // Defensive: a fan-out run's body-only partials ({contextKey}.chunk-{index}) are deleted at
            // finalize, but a mid-finalize crash could leak one — never promote it as if it were a
            // context file (§6.3).
            if (str_contains($contextKey, '.chunk-')) {
                continue;
            }

            $result = $this->feedContextResultRepository->findLatestForContext($feed, $contextKey);

            if (null !== $result && $result->isBlocked()) {
                // Keep the live canonical file; retain the blocked candidate in staging for inspection.
                continue;
            }

            // Published (or no result / no gate): swap the candidate in and drop the staging copy.
            $this->feedFilesystem->writeStream($path, $this->feedTmpFilesystem->readStream($path));
            $this->feedTmpFilesystem->delete($path);

            if (null !== $result && $result->isPublished()) {
                $publishedContexts[$contextKey] = $result;
            }
        }

        // All files are on canonical storage now; push each published context's file set to its
        // matching delivery targets. Delivery is best-effort and never throws.
        foreach ($publishedContexts as $result) {
            $this->deliveryService->deliver($feed, $result, $result->getPaths());
        }

        $feed->setLastGeneratedAt(new \DateTimeImmutable());
    }
}
