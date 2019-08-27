<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use InvalidArgumentException;
use League\Flysystem\FilesystemInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\StringsException;
use function Safe\fclose;
use function Safe\fopen;
use function Safe\fwrite;
use function Safe\ob_end_clean;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Event\FeedChunkGeneratedEvent;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeedChunk;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Workflow\Registry;
use Twig\Environment;

final class GenerateFeedChunkHandler implements MessageHandlerInterface
{
    /** @var FeedRepositoryInterface */
    private $feedRepository;

    /** @var ObjectManager */
    private $feedManager;

    /** @var FeedTypeRegistryInterface */
    private $feedTypeRegistry;

    /** @var Environment */
    private $twig;

    /** @var FilesystemInterface */
    private $filesystem;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var Registry */
    private $workflowRegistry;

    public function __construct(
        FeedRepositoryInterface $feedRepository,
        ObjectManager $feedManager,
        FeedTypeRegistryInterface $feedTypeRegistry,
        Environment $twig,
        FilesystemInterface $filesystem,
        EventDispatcherInterface $eventDispatcher,
        Registry $workflowRegistry
    ) {
        $this->feedRepository = $feedRepository;
        $this->feedManager = $feedManager;
        $this->feedTypeRegistry = $feedTypeRegistry;
        $this->twig = $twig;
        $this->filesystem = $filesystem;
        $this->eventDispatcher = $eventDispatcher;
        $this->workflowRegistry = $workflowRegistry;
    }

    public function __invoke(GenerateFeedChunk $message): void
    {
        /** @var FeedInterface|null $feed */
        $feed = $this->feedRepository->find($message->getFeedId());

        if (null === $feed) {
            throw new UnrecoverableMessageHandlingException('Feed does not exist');
        }

        try {
            $workflow = $this->workflowRegistry->get($feed, FeedGraph::GRAPH);
        } catch (InvalidArgumentException $e) {
            throw new UnrecoverableMessageHandlingException('An error occurred when trying to get the workflow for the feed', 0, $e);
        }

        try {
            $feedType = $this->feedTypeRegistry->get($feed->getFeedType());

            $items = $feedType->getDataProvider()->getItems($message->getBatch());

            $normalizer = $feedType->getNormalizer();

            $template = $this->twig->load($feedType->getTemplate());

            /** @var ChannelInterface $channel */
            foreach ($feed->getChannels() as $channel) {
                foreach ($channel->getLocales() as $locale) {
                    $fp = fopen('php://memory',
                        'w+b'); // needs to be w+ since we use the same stream later to read from

                    ob_start(static function ($buffer) use ($fp) {
                        fwrite($fp, $buffer);
                    }, 8192);

                    foreach ($items as $item) {
                        $arr = $normalizer->normalize($item, $channel->getCode(), $locale->getCode());
                        foreach ($arr as $val) {
                            $template->displayBlock('item', ['item' => $val]);
                        }
                    }

                    ob_end_clean();

                    $path = $this->getPath($feed, $channel->getCode(), $locale->getCode());
                    $res = $this->filesystem->writeStream($path, $fp);

                    try {
                        // tries to close the file pointer although it may already have been closed by flysystem
                        fclose($fp);
                    } catch (FilesystemException $e) {
                    }

                    if (false === $res) {
                        throw new RuntimeException('An error occurred when trying to write a feed item');
                    }
                }
            }

            $this->feedRepository->incrementFinishedBatches($feed);

            $this->eventDispatcher->dispatch(new FeedChunkGeneratedEvent($feed));
        } catch (Exception $e) {
            dd($e->getMessage());
            $workflow->apply($feed, FeedGraph::TRANSITION_ERRORED);

            $this->feedManager->flush();
        }
    }

    /**
     * @throws StringsException
     */
    private function getPath(FeedInterface $feed, string $channel, string $locale): string
    {
        $dir = sprintf('%s/%s/%s', $feed->getUuid(), $channel, $locale);

        do {
            $path = $dir . '/' . uniqid('partial-', true);
        } while ($this->filesystem->has($path));

        return $path;
    }
}
