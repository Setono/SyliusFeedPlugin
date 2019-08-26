<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Controller\Action;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use RuntimeException;
use Safe\Exceptions\StringsException;
use function Safe\fclose;
use function Safe\fread;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Resolver\FeedPathResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ShowFeedAction
{
    /** @var FeedRepositoryInterface */
    private $repository;

    /** @var ChannelContextInterface */
    private $channelContext;

    /** @var LocaleContextInterface */
    private $localeContext;

    /** @var FeedPathResolverInterface */
    private $feedPathResolver;

    /** @var FilesystemInterface */
    private $filesystem;

    public function __construct(
        FeedRepositoryInterface $repository,
        ChannelContextInterface $channelContext,
        LocaleContextInterface $localeContext,
        FeedPathResolverInterface $feedPathResolver,
        FilesystemInterface $filesystem
    ) {
        $this->repository = $repository;
        $this->channelContext = $channelContext;
        $this->localeContext = $localeContext;
        $this->feedPathResolver = $feedPathResolver;
        $this->filesystem = $filesystem;
    }

    /**
     * @throws FileNotFoundException
     * @throws StringsException
     */
    public function __invoke(string $uuid): StreamedResponse
    {
        $feed = $this->repository->findOneByUuid($uuid);
        if (null === $feed) {
            throw new NotFoundHttpException(sprintf('The feed with id %s does not exist', $uuid));
        }

        $channelCode = $this->channelContext->getChannel()->getCode();
        $localeCode = $this->localeContext->getLocaleCode();

        $feedPath = $this->feedPathResolver->resolve($feed, $channelCode, $localeCode);

        if (!$this->filesystem->has($feedPath)) {
            throw new NotFoundHttpException(sprintf('The feed with id %s has not been generated', $uuid));
        }

        $stream = $this->filesystem->readStream($feedPath);
        if (false === $stream) {
            throw new RuntimeException(sprintf('An error occurred trying to read the feed file %s', $feedPath));
        }

        $response = new StreamedResponse();
        $response->setCallback(static function () use ($stream) {
            while (!feof($stream)) {
                echo fread($stream, 8192);

                flush();
            }

            flush();

            fclose($stream);
        });

        return $response;
    }
}
