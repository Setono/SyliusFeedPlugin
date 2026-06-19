<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Writer;

/**
 * Typed, immutable configuration for a writer family (§7). A {@see \Setono\SyliusFeedPlugin\Format\FormatInterface}
 * returns a concrete implementation (e.g. {@see XmlWriterConfig}) describing the structural shape
 * its writer should produce; each writer narrows to the config type it understands.
 */
interface WriterConfigInterface
{
    /**
     * Returns a copy carrying feed-level document metadata (e.g. title/link/description) that the
     * writer may render in its preamble. Writers without a preamble (e.g. CSV) ignore it.
     *
     * @param array<string, string> $metadata
     */
    public function withFeedMetadata(array $metadata): self;
}
