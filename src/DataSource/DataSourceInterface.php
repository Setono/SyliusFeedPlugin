<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataSource;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Filter\FilterSet;

/**
 * Yields the source entities for a feed type, in batches, given a context + filters (§5).
 */
interface DataSourceInterface
{
    public function getResourceClass(): string;

    /**
     * MUST stream/batch, not load all into memory.
     *
     * @return iterable<object>
     */
    public function getItems(FeedContext $context, FilterSet $filters): iterable;

    public function count(FeedContext $context, FilterSet $filters): int;
}
