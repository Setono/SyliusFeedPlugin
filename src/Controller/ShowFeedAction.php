<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Controller;

use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Streams a generated feed file from canonical storage (§9.2). The path is `{code}/{filename}`,
 * e.g. `/feed/google/web_en_us_usd.xml`.
 */
final class ShowFeedAction
{
    public function __construct(private readonly FilesystemOperator $feedFilesystem)
    {
    }

    public function __invoke(string $code, string $filename): Response
    {
        $path = sprintf('%s/%s', $code, $filename);

        if (!$this->feedFilesystem->fileExists($path)) {
            throw new NotFoundHttpException(sprintf('No generated feed file at "%s"', $path));
        }

        $contentType = str_ends_with($filename, '.csv') ? 'text/csv' : 'application/xml';

        return new StreamedResponse(function () use ($path): void {
            $stream = $this->feedFilesystem->readStream($path);
            fpassthru($stream);
            fclose($stream);
        }, Response::HTTP_OK, ['Content-Type' => $contentType]);
    }
}
