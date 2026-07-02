<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

/**
 * The outcome of generating one context's feed file (§11): where it was written, how many items
 * were included vs excluded, the size of the produced file, and a bounded sample of per-item
 * exclusion reasons.
 */
final class GenerationResult
{
    /**
     * @param list<array{item: ?string, reason: string}> $errors
     */
    public function __construct(
        public readonly string $path,
        public readonly int $itemCount,
        public readonly int $excludedCount,
        public readonly int $bytes = 0,
        public readonly array $errors = [],
    ) {
    }
}
