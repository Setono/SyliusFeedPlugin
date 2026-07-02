<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Writer\FeedWriterInterface;
use Setono\SyliusFeedPlugin\Writer\SplitManifestInterface;
use Setono\SyliusFeedPlugin\Writer\WriterConfigInterface;

/**
 * Streams a context's items to storage, splitting into multiple parts when a size limit is reached
 * and optionally gzipping each written file (§12). Reuses the resolved {@see FeedWriterInterface}
 * and its config for every part, so no writer logic is duplicated.
 */
interface OutputWriterInterface
{
    /**
     * @param iterable<\Setono\SyliusFeedPlugin\Item\FeedItem> $items
     * @param array{maxItems?: int, maxBytes?: int}            $splitLimit effective limit; empty = never split
     */
    public function write(
        iterable $items,
        FeedWriterInterface $writer,
        WriterConfigInterface $config,
        FeedContext $context,
        string $directory,
        string $basename,
        string $extension,
        array $splitLimit,
        bool $gzip,
        SplitManifestInterface $manifest,
    ): OutputResult;
}
