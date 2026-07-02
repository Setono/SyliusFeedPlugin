<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Writer;

use Setono\SyliusFeedPlugin\Context\FeedContext;

/**
 * The no-op manifest strategy (§12): each produced part is a complete, self-describing document
 * (a full XML document, or a CSV with its own header row), so no manifest file is emitted — the
 * parts stand on their own.
 */
final class NoneSplitManifest implements SplitManifestInterface
{
    public const TYPE = 'none';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function writeManifest($manifestStream, array $partPaths, FeedContext $context, WriterConfigInterface $config): void
    {
        // Intentionally empty: the parts are self-describing, so there is nothing to write and the
        // generator emits no manifest file.
    }
}
