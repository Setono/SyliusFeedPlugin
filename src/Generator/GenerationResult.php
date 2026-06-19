<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

/**
 * The outcome of generating one context's feed file: where it was written and how many items
 * were included vs excluded.
 */
final class GenerationResult
{
    public function __construct(
        public readonly string $path,
        public readonly int $itemCount,
        public readonly int $excludedCount,
    ) {
    }
}
