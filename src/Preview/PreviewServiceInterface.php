<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Preview;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Model\FeedInterface;

/**
 * Runs the generator's per-item pipeline over a bounded sample of a feed's source items without
 * writing anything (§11), so an admin can see the include/exclude funnel, the mapped output of
 * sample items, and why items are dropped before committing to a full run.
 */
interface PreviewServiceInterface
{
    /**
     * Preview at most $limit source items per source through the full pipeline (pre-filter → map →
     * post-filter → item-built event → validation), returning the funnel and bounded samples.
     */
    public function preview(FeedInterface $feed, FeedContext $context, int $limit = 50): PreviewResult;

    /**
     * Single-item tester: find the source item whose resolved `id`/`g:id` equals $id within a
     * bounded scan of the data source and report whether it would be included, its mapped output,
     * and — when excluded — the rule that dropped it.
     *
     * @return array{included: bool, output: array<string, mixed>, reason: ?string}
     */
    public function previewItem(FeedInterface $feed, FeedContext $context, string $id): array;
}
