<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Controller;

use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Streams a generated feed file from canonical storage (§9.2). The path is `{code}/{filename}`,
 * e.g. `/feed/google/web_en_us_usd.xml`. The feed must exist and be enabled, and the file present.
 */
final class ShowFeedAction
{
    private const CONTENT_TYPES = [
        'xml' => 'application/xml',
        'csv' => 'text/csv',
        'json' => 'application/json',
        'txt' => 'text/plain',
    ];

    public function __construct(
        private readonly FeedRepositoryInterface $feedRepository,
        private readonly FilesystemOperator $feedFilesystem,
    ) {
    }

    public function __invoke(string $code, string $filename): Response
    {
        $feed = $this->feedRepository->findOneBy(['code' => $code]);
        if (!$feed instanceof FeedInterface || !$feed->isEnabled()) {
            throw new NotFoundHttpException(sprintf('There is no enabled feed with code "%s"', $code));
        }

        $path = sprintf('%s/%s', $code, $filename);
        if (!$this->feedFilesystem->fileExists($path)) {
            throw new NotFoundHttpException(sprintf('No generated feed file at "%s"', $path));
        }

        $extension = strtolower(pathinfo($filename, \PATHINFO_EXTENSION));
        $contentType = self::CONTENT_TYPES[$extension] ?? 'application/octet-stream';

        return new StreamedResponse(function () use ($path): void {
            $stream = $this->feedFilesystem->readStream($path);
            fpassthru($stream);
            fclose($stream);
        }, Response::HTTP_OK, ['Content-Type' => $contentType]);
    }
}
