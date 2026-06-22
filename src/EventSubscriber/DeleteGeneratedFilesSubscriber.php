<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventSubscriber;

use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

/**
 * Cleans up the temporary files of a feed whose run ended without publishing — on `fail` (an error
 * mid-generation) or `reset` — leaving the canonical (last-good) feed untouched.
 */
final class DeleteGeneratedFilesSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly FilesystemOperator $feedTmpFilesystem)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            sprintf('workflow.%s.completed.%s', FeedGraph::GRAPH, FeedGraph::TRANSITION_FAIL) => 'onCompleted',
            sprintf('workflow.%s.completed.%s', FeedGraph::GRAPH, FeedGraph::TRANSITION_RESET) => 'onCompleted',
        ];
    }

    public function onCompleted(CompletedEvent $event): void
    {
        $feed = $event->getSubject();
        if (!$feed instanceof FeedInterface) {
            return;
        }

        $this->feedTmpFilesystem->deleteDirectory((string) $feed->getCode());
    }
}
