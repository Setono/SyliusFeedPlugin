<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventListener;

use InvalidArgumentException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteFile;
use RuntimeException;
use Setono\SyliusFeedPlugin\Generator\FeedPathGeneratorInterface;
use Setono\SyliusFeedPlugin\Generator\TemporaryFeedPathGenerator;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;

final class MoveGeneratedFeedSubscriber implements EventSubscriberInterface
{
    /** @var FilesystemInterface|FilesystemOperator */
    private $temporaryFilesystem;

    /** @var FilesystemInterface|FilesystemOperator */
    private $filesystem;

    /**
     * @param FilesystemInterface|FilesystemOperator $temporaryFilesystem
     * @param FilesystemInterface|FilesystemOperator $filesystem
     */
    public function __construct(
        $temporaryFilesystem,
        $filesystem,
        private readonly FeedPathGeneratorInterface $temporaryFeedPathGenerator,
        private readonly FeedPathGeneratorInterface $feedPathGenerator,
    ) {
        if (interface_exists(FilesystemInterface::class) && $temporaryFilesystem instanceof FilesystemInterface) {
            $this->temporaryFilesystem = $temporaryFilesystem;
        } elseif ($temporaryFilesystem instanceof FilesystemOperator) {
            $this->temporaryFilesystem = $temporaryFilesystem;
        } else {
            throw new InvalidArgumentException(sprintf(
                'The filesystem must be an instance of %s or %s',
                FilesystemInterface::class,
                FilesystemOperator::class,
            ));
        }
        if (interface_exists(FilesystemInterface::class) && $filesystem instanceof FilesystemInterface) {
            $this->filesystem = $filesystem;
        } elseif ($filesystem instanceof FilesystemOperator) {
            $this->filesystem = $filesystem;
        } else {
            throw new InvalidArgumentException(sprintf(
                'The filesystem must be an instance of %s or %s',
                FilesystemInterface::class,
                FilesystemOperator::class,
            ));
        }
    }

    public static function getSubscribedEvents(): array
    {
        $event = sprintf('workflow.%s.transition.%s', FeedGraph::GRAPH, FeedGraph::TRANSITION_PROCESSED);

        return [
            $event => 'move',
        ];
    }

    public function move(TransitionEvent $event): void
    {
        $feed = $event->getSubject();

        if (!$feed instanceof FeedInterface) {
            return;
        }

        /** @var ChannelInterface $channel */
        foreach ($feed->getChannels() as $channel) {
            foreach ($channel->getLocales() as $locale) {
                $temporaryDir = $this->temporaryFeedPathGenerator->generate(
                    $feed,
                    (string) $channel->getCode(),
                    (string) $locale->getCode(),
                );
                $temporaryFilesystem = $this->temporaryFilesystem;
                $temporaryPath = TemporaryFeedPathGenerator::getBaseFile($temporaryDir);
                $tempFile = $temporaryFilesystem->readStream((string) $temporaryPath);
                if (!\is_resource($tempFile)) {
                    throw new \RuntimeException(sprintf(
                        'The file with path "%s" could not be found',
                        $temporaryPath,
                    ));
                }

                // move the file from the temporary location to a temp file in the *not* temporary directory
                $newPath = $this->feedPathGenerator->generate(
                    $feed,
                    (string) $channel->getCode(),
                    (string) $locale->getCode(),
                );
                $path = sprintf('%s/%s', $newPath->getPath(), uniqid('feed-', true));
                $filesystem = $this->filesystem;

                if (interface_exists(FilesystemInterface::class) && $filesystem instanceof FilesystemInterface) {
                    /** @var resource|false $res */
                    $res = $filesystem->writeStream($path, $tempFile);

                    if (false === $res) {
                        throw new RuntimeException('An error occurred when trying to write the feed to the filesystem');
                    }
                } else {
                    $filesystem->writeStream($path, $tempFile);
                }

                try {
                    $filesystem->delete((string) $newPath);
                } catch (FileNotFoundException|UnableToDeleteFile) {
                }

                if (interface_exists(FilesystemInterface::class) && $filesystem instanceof FilesystemInterface) {
                    $filesystem->rename($path, (string) $newPath);
                } else {
                    $filesystem->move($path, (string) $newPath);
                }

                $temporaryFilesystem->delete((string) $temporaryPath);
            }
        }
    }
}
