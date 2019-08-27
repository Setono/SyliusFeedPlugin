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
use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;
use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class MoveGeneratedFeedSubscriber implements EventSubscriberInterface
{
    /** @var FeedTypeRegistryInterface */
    private $feedTypeRegistry;

    /** @var FilesystemInterface */
    private $temporaryFilesystem;

    /** @var FilesystemInterface */
    private $filesystem;

    /** @var Environment */
    private $twig;

    public function __construct(
        FeedTypeRegistryInterface $feedTypeRegistry,
        FilesystemInterface $temporaryFilesystem,
        FilesystemInterface $filesystem,
        Environment $twig
    ) {
        $this->feedTypeRegistry = $feedTypeRegistry;
        $this->temporaryFilesystem = $temporaryFilesystem;
        $this->filesystem = $filesystem;
        $this->twig = $twig;
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
     * @throws LoaderError
     * @throws RuntimeError
     * @throws StringsException
     * @throws SyntaxError
     * @throws Throwable
     * @throws FileExistsException
     */
    public function move(TransitionEvent $event): void
    {
        $feed = $event->getSubject();

        if (!$feed instanceof FeedInterface) {
            return;
        }

        $feedType = $this->feedTypeRegistry->get($feed->getFeedType());

        $extension = $this->getExtension($feedType);

        /** @var ChannelInterface $channel */
        foreach ($feed->getChannels() as $channel) {
            foreach ($channel->getLocales() as $locale) {
                $temporaryFilesystemPath = sprintf('%s/%s/%s/_feed', $feed->getUuid(), $channel->getCode(), $locale->getCode());
                $tempFile = $this->temporaryFilesystem->readStream($temporaryFilesystemPath);
                if (false === $tempFile) {
                    throw new FilesystemException(sprintf('The file with path "%s" could not be found', $temporaryFilesystemPath));
                }

                $dir = sprintf('%s/%s', $channel->getCode(), $locale->getCode());
                $path = sprintf('%s/%s', $dir, uniqid('feed-', true));
                $newPath = sprintf('%s/%s.%s', $dir, $feed->getUuid(), $extension);
                $res = $this->filesystem->writeStream($path, $tempFile);

                if (false === $res) {
                    throw new RuntimeException('An error occurred when trying to the feed to the filesystem');
                }

                try {
                    $this->filesystem->delete($newPath);
                } catch (FileNotFoundException $e) {
                }
                $this->filesystem->rename($path, $newPath);

                $this->temporaryFilesystem->delete($temporaryFilesystemPath);
            }
        }
    }

    /**
     * @throws Throwable
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function getExtension(FeedTypeInterface $feedType): string
    {
        $template = $this->twig->load($feedType->getTemplate());

        return $template->renderBlock('extension');
    }
}
