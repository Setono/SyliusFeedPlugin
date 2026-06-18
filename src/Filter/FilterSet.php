<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Filter;

use Setono\SyliusFeedPlugin\Model\FeedFilterInterface;

/**
 * The immutable collection of a source's filters passed to DataSourceInterface::getItems(),
 * partitioned into `pre` (query-pushable) and `post` (per-item) stages (§4.2).
 *
 * Filter evaluation lands in M6; for now this carries the partitioned filters so the data
 * source signature is stable.
 */
final class FilterSet
{
    /** @var list<FeedFilterInterface> */
    private readonly array $pre;

    /** @var list<FeedFilterInterface> */
    private readonly array $post;

    /**
     * @param iterable<FeedFilterInterface> $filters
     */
    public function __construct(iterable $filters = [])
    {
        $pre = [];
        $post = [];

        foreach ($filters as $filter) {
            if (FeedFilterInterface::STAGE_PRE === $filter->getStage()) {
                $pre[] = $filter;
            } else {
                $post[] = $filter;
            }
        }

        $this->pre = $pre;
        $this->post = $post;
    }

    /**
     * @return list<FeedFilterInterface>
     */
    public function getPreFilters(): array
    {
        return $this->pre;
    }

    /**
     * @return list<FeedFilterInterface>
     */
    public function getPostFilters(): array
    {
        return $this->post;
    }

    public function isEmpty(): bool
    {
        return [] === $this->pre && [] === $this->post;
    }
}
