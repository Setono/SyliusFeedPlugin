<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Handler;

use Doctrine\Persistence\ObjectManager;
use InvalidArgumentException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\Generator\FeedPathGeneratorInterface;
use Setono\SyliusFeedPlugin\Generator\TemporaryFeedPathGenerator;
use Setono\SyliusFeedPlugin\Message\Command\FinishGeneration;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Workflow\Registry;
use Throwable;
use Twig\Environment;

final class FinishGenerationHandler implements MessageHandlerInterface
{
    use GetFeedTrait;

    private ObjectManager $feedManager;

    private FilesystemOperator $filesystem;

    private Registry $workflowRegistry;

    private Environment $twig;

    private FeedTypeRegistryInterface $feedTypeRegistry;

    private FeedPathGeneratorInterface $temporaryFeedPathGenerator;

    private LoggerInterface $logger;

    public function __construct(
        FeedRepositoryInterface $feedRepository,
        ObjectManager $feedManager,
        FilesystemOperator $filesystem,
        Registry $workflowRegistry,
        Environment $twig,
        FeedTypeRegistryInterface $feedTypeRegistry,
        FeedPathGeneratorInterface $temporaryFeedPathGenerator,
        LoggerInterface $logger
    ) {
        $this->feedRepository = $feedRepository;
        $this->feedManager = $feedManager;
        $this->filesystem = $filesystem;
        $this->workflowRegistry = $workflowRegistry;
        $this->twig = $twig;
        $this->feedTypeRegistry = $feedTypeRegistry;
        $this->temporaryFeedPathGenerator = $temporaryFeedPathGenerator;
        $this->logger = $logger;
    }

    public function __invoke(FinishGeneration $message): void
    {
        $feed = $this->getFeed($message->getFeedId());

        try {
            $workflow = $this->workflowRegistry->get($feed, FeedGraph::GRAPH);
        } catch (InvalidArgumentException $e) {
            throw new UnrecoverableMessageHandlingException(
                'An error occurred when trying to get the workflow for the feed',
                0,
                $e
            );
        }

        try {
            $feedType = $this->feedTypeRegistry->get((string) $feed->getFeedType());

            /** @var ChannelInterface $channel */
            foreach ($feed->getChannels() as $channel) {
                foreach ($channel->getLocales() as $locale) {
                    $dir = $this->temporaryFeedPathGenerator->generate($feed, (string) $channel->getCode(), (string) $locale->getCode());

                    $batchStream = $this->getBatchStream();

                    [$feedStart, $feedEnd] = $this->getFeedParts($feed, $feedType, $channel, $locale);

                    fwrite($batchStream, $feedStart);

                    $files = $this->filesystem->listContents((string) $dir);
                    foreach ($files as $file) {
                        if (TemporaryFeedPathGenerator::BASE_FILENAME === basename($file->path())) {
                            continue;
                        }

                        $fp = $this->filesystem->readStream($file->path());
                        if (false === $fp) {
                            throw new \RuntimeException(sprintf(
                                'The file "%s" could not be opened as a resource',
                                $file['path']
                            ));
                        }

                        while (!feof($fp)) {
                            fwrite($batchStream, fread($fp, 8192));
                        }

                        fclose($fp);

                        $this->filesystem->delete($file->path());
                    }

                    fwrite($batchStream, $feedEnd);

                    $this->filesystem->writeStream((string) TemporaryFeedPathGenerator::getBaseFile($dir), $batchStream);

                    // tries to close the file pointer although it may already have been closed by flysystem
                    fclose($batchStream);
                }
            }

            if (!$workflow->can($feed, FeedGraph::TRANSITION_PROCESSED)) {
                throw new RuntimeException(sprintf(
                    'The feed with id: %d can not be marked as ready because the feed is in a wrong state (%s)',
                    (int) $feed->getId(),
                    $feed->getState()
                ));
            }

            $workflow->apply($feed, FeedGraph::TRANSITION_PROCESSED);

            $this->feedManager->flush();
        } catch (Throwable $e) {
            $this->logger->critical($e->getMessage(), ['feedId' => $feed->getId()]);

            if ($workflow->can($feed, FeedGraph::TRANSITION_ERRORED)) {
                $workflow->apply($feed, FeedGraph::TRANSITION_ERRORED);
                $this->feedManager->flush();
            }

            throw $e;
        }
    }

    /**
     * @return resource
     */
    private function getBatchStream()
    {
        // needs to be w+ since we use the same stream later to read from
        return fopen('php://temp', 'w+b');
    }

    private function getFeedParts(
        FeedInterface $feed,
        FeedTypeInterface $feedType,
        ChannelInterface $channel,
        LocaleInterface $locale
    ): array {
        $template = $this->twig->load('@SetonoSyliusFeedPlugin/Feed/feed.txt.twig');

        $content = $template->render(
            array_merge(
                $feedType->getFeedContext()->getContext($feed, $channel, $locale),
                ['feed' => $feedType->getTemplate()]
            )
        );

        return explode('<!-- ITEM_BOUNDARY -->', $content);
    }
}
