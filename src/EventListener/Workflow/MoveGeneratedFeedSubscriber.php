<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventListener\Workflow;

use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

/**
 * On `complete`, swaps the feed's freshly generated files from temporary to canonical storage so
 * the public feed is never served half-written (§6.3): the canonical directory is replaced with the
 * temporary one, then the temporary copy is removed and the feed is stamped as generated.
 */
final class MoveGeneratedFeedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly FilesystemOperator $feedTmpFilesystem,
        private readonly FilesystemOperator $feedFilesystem,
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

        $this->feedFilesystem->deleteDirectory($directory);

        foreach ($this->feedTmpFilesystem->listContents($directory, true) as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $this->feedFilesystem->writeStream($item->path(), $this->feedTmpFilesystem->readStream($item->path()));
        }

        $this->feedTmpFilesystem->deleteDirectory($directory);

        $feed->setLastGeneratedAt(new \DateTimeImmutable());
    }
}
