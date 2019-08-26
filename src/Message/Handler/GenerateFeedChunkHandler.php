<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Handler;

use InvalidArgumentException;
use League\Flysystem\FileExistsException;
use League\Flysystem\FilesystemInterface;
use RuntimeException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\OutcontrolException;
use Safe\Exceptions\StringsException;
use function Safe\fclose;
use function Safe\fopen;
use function Safe\fwrite;
use function Safe\ob_end_clean;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeedChunk;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class GenerateFeedChunkHandler implements MessageHandlerInterface
{
    /** @var FeedRepositoryInterface */
    private $feedRepository;

    /** @var FeedTypeRegistryInterface */
    private $feedTypeRegistry;

    /** @var Environment */
    private $twig;

    /** @var FilesystemInterface */
    private $filesystem;

    public function __construct(
        FeedRepositoryInterface $feedRepository,
        FeedTypeRegistryInterface $feedTypeRegistry,
        Environment $twig,
        FilesystemInterface $filesystem
    ) {
        $this->feedRepository = $feedRepository;
        $this->feedTypeRegistry = $feedTypeRegistry;
        $this->twig = $twig;
        $this->filesystem = $filesystem;
    }

    /**
     * @throws FilesystemException
     * @throws StringsException
     * @throws FileExistsException
     * @throws OutcontrolException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function __invoke(GenerateFeedChunk $message): void
    {
        /** @var FeedInterface|null $feed */
        $feed = $this->feedRepository->find($message->getFeedId());

        if (null === $feed) {
            throw new InvalidArgumentException('Feed does not exist'); // todo better exception
        }

        $feedType = $this->feedTypeRegistry->get($feed->getFeedType());

        $items = $feedType->getDataProvider()->getItems($message->getBatch());

        $normalizer = $feedType->getNormalizer();

        $template = $this->twig->load($feedType->getTemplate());

        /** @var ChannelInterface $channel */
        foreach ($feed->getChannels() as $channel) {
            foreach ($channel->getLocales() as $locale) {
                $fp = fopen('php://memory', 'w+b'); // needs to be w+ since we use the same stream later to read from

                ob_start(static function ($buffer) use ($fp) {
                    fwrite($fp, $buffer);
                }, 1024);

                foreach ($items as $item) {
                    $arr = $normalizer->normalize($item, $channel->getCode(), $locale->getCode());
                    foreach ($arr as $val) {
                        $template->displayBlock('item', $val);
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
