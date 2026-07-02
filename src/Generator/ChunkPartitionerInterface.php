<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Model\FeedInterface;

/**
 * Plans the fan-out chunk ranges for a context (§6.3): partitions the source's id space into an
 * ordered, non-overlapping list of {@see ChunkRange}s that together cover every id, so the ordered
 * concatenation of the chunks reproduces the inline order. Returns fewer than two ranges (typically
 * an empty list) when the source is too small to be worth fanning out or cannot be partitioned by id
 * — the caller then generates inline.
 */
interface ChunkPartitionerInterface
{
    /**
     * @return list<ChunkRange> ordered ranges covering the source ids, or `[]` to stay inline
     */
    public function partition(FeedInterface $feed, FeedContext $context, int $chunkSize): array;
}
