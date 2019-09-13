<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventListener;

use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use RuntimeException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Generator\FeedPathGeneratorInterface;
use Setono\SyliusFeedPlugin\Generator\TemporaryFeedPathGenerator;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Throwable;

final class MoveGeneratedFeedSubscriber implements EventSubscriberInterface
{
    /** @var FilesystemInterface */
    private $temporaryFilesystem;

    /** @var FilesystemInterface */
    private $filesystem;

    /** @var FeedPathGeneratorInterface */
    private $temporaryFeedPathGenerator;

    /** @var FeedPathGeneratorInterface */
    private $feedPathGenerator;

    public function __construct(
        FilesystemInterface $temporaryFilesystem,
        FilesystemInterface $filesystem,
        FeedPathGeneratorInterface $temporaryFeedPathGenerator,
        FeedPathGeneratorInterface $feedPathGenerator
    ) {
        $this->temporaryFilesystem = $temporaryFilesystem;
        $this->filesystem = $filesystem;
        $this->temporaryFeedPathGenerator = $temporaryFeedPathGenerator;
        $this->feedPathGenerator = $feedPathGenerator;
    }

    /**
     * @throws StringsException
     */
    public static function getSubscribedEvents(): array
    {
        $event = sprintf('workflow.%s.transition.%s', FeedGraph::GRAPH, FeedGraph::TRANSITION_PROCESSED);

        return [
            $event => 'move',
        ];
    }

    /**
     * @throws FileNotFoundException
     * @throws FilesystemException
     * @throws StringsException
     * @throws Throwable
     * @throws FileExistsException
     */
    public function move(TransitionEvent $event): void
    {
        $feed = $event->getSubject();

        if (!$feed instanceof FeedInterface) {
            return;
        }

        /** @var ChannelInterface $channel */
        foreach ($feed->getChannels() as $channel) {
            foreach ($channel->getLocales() as $locale) {
                $temporaryDir = $this->temporaryFeedPathGenerator->generate($feed, $channel->getCode(), $locale->getCode());
                $temporaryPath = TemporaryFeedPathGenerator::getBaseFile($temporaryDir);
                $tempFile = $this->temporaryFilesystem->readStream((string) $temporaryPath);
                if (false === $tempFile) {
                    throw new FilesystemException(sprintf('The file with path "%s" could not be found', $temporaryPath));
                }

                // move the file from the temporary location to a temp file in the *not* temporary directory
                $newPath = $this->feedPathGenerator->generate($feed, $channel->getCode(), $locale->getCode());
                $path = sprintf('%s/%s', $newPath->getPath(), uniqid('feed-', true));
                $res = $this->filesystem->writeStream($path, $tempFile);

                if (false === $res) {
                    throw new RuntimeException('An error occurred when trying to write the feed to the filesystem');
                }

                try {
                    $this->filesystem->delete((string) $newPath);
                } catch (FileNotFoundException $e) {
                }

                $this->filesystem->rename($path, (string) $newPath);

                $this->temporaryFilesystem->delete((string) $temporaryPath);
            }
        }
    }
}
