<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Webmozart\Assert\Assert;

/**
 * An inclusive id range `[start, end]` covering a slice of a data source's entities (§6.3). A
 * fan-out run partitions a source's id space into ordered, non-overlapping ranges so each chunk
 * yields exactly its slice and the ordered concatenation of the chunks reproduces the inline order.
 */
final class ChunkRange
{
    public function __construct(
        public readonly int $start,
        public readonly int $end,
    ) {
        Assert::greaterThanEq($end, $start, 'A chunk range end must not be smaller than its start');
    }
}
