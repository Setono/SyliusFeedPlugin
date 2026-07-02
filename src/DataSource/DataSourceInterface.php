<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataSource;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Filter\FilterSet;
use Setono\SyliusFeedPlugin\Generator\ChunkRange;

/**
 * Yields the source entities for a feed type, in batches, given a context + filters (§5).
 */
interface DataSourceInterface
{
    /**
     * @return class-string the source entity class this data source iterates
     */
    public function getResourceClass(): string;

    /**
     * MUST stream/batch, not load all into memory.
     *
     * @return iterable<object>
     */
    public function getItems(FeedContext $context, FilterSet $filters): iterable;

    public function count(FeedContext $context, FilterSet $filters): int;

    /**
     * The inclusive `[min, max]` id bounds of the entities this source would yield for the given
     * context and filters, or null when the source cannot be partitioned by id (e.g. an in-memory or
     * collection source) or would yield nothing. Used to plan chunk ranges for fan-out generation of
     * a large source (§6.3); returning null keeps that source on the inline path.
     */
    public function getIdRange(FeedContext $context, FilterSet $filters): ?ChunkRange;

    /**
     * Streams only the entities whose id falls within the given chunk range, in ascending id order,
     * so a fan-out chunk yields exactly its slice of the source (§6.3). MUST stream/batch. The
     * ordered concatenation of every range must reproduce the {@see getItems()} order exactly.
     *
     * @return iterable<object>
     */
    public function getItemsInRange(FeedContext $context, FilterSet $filters, ChunkRange $range): iterable;
}
