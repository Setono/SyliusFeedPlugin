<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Filter\FilterSet;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;
use Webmozart\Assert\Assert;

/**
 * Partitions a single-source feed's id space into fan-out chunk ranges (§6.3). The number of chunks
 * is `ceil(count / chunkSize)` and the id span `[min, max]` is divided into that many equal-width,
 * ordered, non-overlapping ranges — so the plan is deterministic, covers every id (even with gaps in
 * the id sequence, and even if a range happens to be empty), and its ordered concatenation reproduces
 * the inline stream. Returns `[]` when the source has one chunk's worth of rows or fewer, or cannot be
 * partitioned by id, keeping that context on the inline path.
 */
final class ChunkPartitioner implements ChunkPartitionerInterface
{
    public function __construct(
        private readonly FeedTypeRegistryInterface $feedTypeRegistry,
    ) {
    }

    public function partition(FeedInterface $feed, FeedContext $context, int $chunkSize): array
    {
        Assert::positiveInteger($chunkSize);

        $source = $this->singleSource($feed);
        if (null === $source) {
            return [];
        }

        $dataSource = $this->feedTypeRegistry->get((string) $source->getFeedType())->getDataSource();
        $filterSet = new FilterSet($source->getFilters());

        $count = $dataSource->count($context, $filterSet);
        $numChunks = (int) ceil($count / $chunkSize);
        if ($numChunks < 2) {
            return [];
        }

        $bounds = $dataSource->getIdRange($context, $filterSet);
        if (null === $bounds) {
            return [];
        }

        $span = $bounds->end - $bounds->start + 1;
        $width = (int) ceil($span / $numChunks);

        $ranges = [];
        $start = $bounds->start;
        while ($start <= $bounds->end) {
            $end = min($start + $width - 1, $bounds->end);
            $ranges[] = new ChunkRange($start, $end);
            $start = $end + 1;
        }

        return count($ranges) >= 2 ? $ranges : [];
    }

    private function singleSource(FeedInterface $feed): ?FeedSourceInterface
    {
        $sources = $feed->getSources();
        if (1 !== $sources->count()) {
            return null;
        }

        $source = $sources->first();

        return $source instanceof FeedSourceInterface ? $source : null;
    }
}
