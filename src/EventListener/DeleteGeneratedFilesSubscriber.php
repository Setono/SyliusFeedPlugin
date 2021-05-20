<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventListener;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\RootViolationException;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Webmozart\Assert\Assert;

final class DeleteGeneratedFilesSubscriber implements EventSubscriberInterface
{
    private FilesystemInterface $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public static function getSubscribedEvents(): array
    {
        $event = sprintf('workflow.%s.transition.%s', FeedGraph::GRAPH, FeedGraph::TRANSITION_ERRORED);

        return [
            $event => 'delete',
        ];
    }

    public function delete(TransitionEvent $event): void
    {
        /** @var FeedInterface|object $feed */
        $feed = $event->getSubject();

        Assert::isInstanceOf($feed, FeedInterface::class);

        try {
            $this->filesystem->deleteDir($feed->getCode());
        } catch (RootViolationException $e) {
        }
    }
}
