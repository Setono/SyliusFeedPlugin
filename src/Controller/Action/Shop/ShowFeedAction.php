<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Controller\Action\Shop;

use League\Flysystem\FilesystemInterface;
use RuntimeException;
use function Safe\fclose;
use function Safe\fread;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Generator\FeedPathGeneratorInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\MimeTypesInterface;

final class ShowFeedAction
{
    /** @var FeedRepositoryInterface */
    private $repository;

    /** @var ChannelContextInterface */
    private $channelContext;

    /** @var LocaleContextInterface */
    private $localeContext;

    /** @var FeedPathGeneratorInterface */
    private $feedPathGenerator;

    /** @var FilesystemInterface */
    private $filesystem;

    /** @var MimeTypesInterface */
    private $mimeTypes;

    public function __construct(
        FeedRepositoryInterface $repository,
        ChannelContextInterface $channelContext,
        LocaleContextInterface $localeContext,
        FeedPathGeneratorInterface $feedPathGenerator,
        FilesystemInterface $filesystem,
        MimeTypesInterface $mimeTypes
    ) {
        $this->repository = $repository;
        $this->channelContext = $channelContext;
        $this->localeContext = $localeContext;
        $this->feedPathGenerator = $feedPathGenerator;
        $this->filesystem = $filesystem;
        $this->mimeTypes = $mimeTypes;
    }

    public function __invoke(string $code): StreamedResponse
    {
        $feed = $this->repository->findOneByCode($code);
        if (null === $feed) {
            throw new NotFoundHttpException(sprintf('The feed with id %s does not exist', $code));
        }

        $channelCode = (string) $this->channelContext->getChannel()->getCode();
        $localeCode = $this->localeContext->getLocaleCode();

        $feedPath = $this->feedPathGenerator->generate($feed, $channelCode, $localeCode);

        if (!$this->filesystem->has((string) $feedPath)) {
            throw new NotFoundHttpException(sprintf('The feed with id %s has not been generated', $code));
        }

        $stream = $this->filesystem->readStream((string) $feedPath);
        if (false === $stream) {
            throw new RuntimeException(sprintf('An error occurred trying to read the feed file %s', $feedPath));
        }

        $contentType = $this->mimeTypes->getMimeTypes($feedPath->getExtension())[0];

        $response = new StreamedResponse();
        $response->headers->set('Content-Type', $contentType);
        $response->setCallback(static function () use ($stream): void {
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
