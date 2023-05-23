<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventListener;

use InvalidArgumentException;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\RootViolationException;
use League\Flysystem\UnableToDeleteDirectory;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Webmozart\Assert\Assert;

/**
 * @psalm-suppress UndefinedDocblockClass
 * @psalm-suppress UndefinedClass
 */
final class DeleteGeneratedFilesSubscriber implements EventSubscriberInterface
{
    /** @var FilesystemInterface|FilesystemOperator */
    private $filesystem;

    /**
     * @psalm-suppress UndefinedDocblockClass
     *
     * @param FilesystemInterface|FilesystemOperator $filesystem
     */
    public function __construct($filesystem)
    {
        if (interface_exists(FilesystemInterface::class) && $filesystem instanceof FilesystemInterface) {
            $this->filesystem = $filesystem;
        } elseif ($filesystem instanceof FilesystemOperator) {
            $this->filesystem = $filesystem;
        } else {
            throw new InvalidArgumentException(sprintf(
                'The filesystem must be an instance of %s or %s',
                FilesystemInterface::class,
                FilesystemOperator::class
            ));
        }
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
            $filesystem = $this->filesystem;
            if (interface_exists(FilesystemInterface::class) && $filesystem instanceof FilesystemInterface) {
                $filesystem->deleteDir($feed->getCode());
            } else {
                $filesystem->deleteDirectory($feed->getCode());
            }
        } catch (RootViolationException|UnableToDeleteDirectory $e) {
        }
    }
}
