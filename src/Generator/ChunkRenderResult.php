<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

/**
 * The outcome of rendering one fan-out chunk's body-only partial (§6.3): how many items the chunk
 * wrote, how many its range excluded, and the bounded exclusion sample — aggregated across chunks by
 * the finalize step into the context's {@see GenerationResult}.
 */
final class ChunkRenderResult
{
    /**
     * @param list<array{item: ?string, reason: string}> $errors
     */
    public function __construct(
        public readonly int $itemCount,
        public readonly int $excludedCount,
        public readonly array $errors = [],
    ) {
    }
}
