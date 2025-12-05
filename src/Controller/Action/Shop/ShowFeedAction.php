<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Controller\Action\Shop;

use InvalidArgumentException;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\FilesystemOperator;
use RuntimeException;
use Setono\SyliusFeedPlugin\Generator\FeedPathGeneratorInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\MimeTypesInterface;

final class ShowFeedAction
{
    /** @var FilesystemInterface|FilesystemOperator */
    private $filesystem;

    /**
     * @param FilesystemInterface|FilesystemOperator $filesystem
     */
    public function __construct(
        private readonly FeedRepositoryInterface $repository,
        private readonly ChannelContextInterface $channelContext,
        private readonly LocaleContextInterface $localeContext,
        private readonly FeedPathGeneratorInterface $feedPathGenerator,
        $filesystem,
        private readonly MimeTypesInterface $mimeTypes,
    ) {
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

    public function __invoke(string $code): StreamedResponse
    {
        $feed = $this->repository->findOneByCode($code);
        if (null === $feed) {
            throw new NotFoundHttpException(sprintf('The feed with id %s does not exist', $code));
        }

        $channelCode = (string) $this->channelContext->getChannel()->getCode();
        $localeCode = $this->localeContext->getLocaleCode();

        $feedPath = $this->feedPathGenerator->generate($feed, $channelCode, $localeCode);

        $filesystem = $this->filesystem;
        if (interface_exists(FilesystemInterface::class) && $filesystem instanceof FilesystemInterface) {
            if (!$filesystem->has((string) $feedPath)) {
                throw new NotFoundHttpException(sprintf('The feed with id %s has not been generated', $code));
            }
        } else {
            if (!$filesystem->fileExists((string) $feedPath)) {
                throw new NotFoundHttpException(sprintf('The feed with id %s has not been generated', $code));
            }
        }

        $stream = $filesystem->readStream((string) $feedPath);
        if (!\is_resource($stream)) {
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
