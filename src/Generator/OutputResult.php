<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

/**
 * The outcome of streaming one context's items to storage (§12): the canonical entry-point path,
 * every file actually written (parts + an optional manifest), the number of items written and the
 * total on-disk size of the written files.
 */
final class OutputResult
{
    /**
     * @param list<string> $paths
     */
    public function __construct(
        public readonly string $primaryPath,
        public readonly array $paths,
        public readonly int $itemCount,
        public readonly int $bytes,
    ) {
    }
}
