<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Filter;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Model\FeedFilterInterface;

/**
 * Evaluates a source's filters against a single item to decide inclusion/exclusion (§11). The
 * `pre`/`post` partitioning is a scheduling concern owned by the generator ({@see FilterSet}); this
 * contract just answers "does any of these filters exclude the item?".
 */
interface FilterEvaluatorInterface
{
    /**
     * Returns the first filter that EXCLUDES the item, or null when every filter passes.
     *
     * @param iterable<FeedFilterInterface> $filters
     */
    public function excludedBy(FeedItem $item, iterable $filters): ?FeedFilterInterface;
}
