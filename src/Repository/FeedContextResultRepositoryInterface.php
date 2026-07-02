<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Repository;

use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<FeedContextResultInterface>
 */
interface FeedContextResultRepositoryInterface extends RepositoryInterface
{
    /**
     * The most recent *published* result for the given feed and context key (§6.6) — the baseline
     * the publish gate compares a new candidate against. Filters on
     * {@see FeedContextResultInterface::PUBLISH_STATE_PUBLISHED} so a just-recorded pending candidate
     * is never its own baseline. Ordered by creation time, newest first.
     */
    public function findLatestPublished(FeedInterface $feed, string $contextKey): ?FeedContextResultInterface;

    /**
     * The most recent result for the given feed and context key regardless of publish state (§6.6) —
     * used by the promotion step to decide whether a staged file was published or blocked. Ordered
     * by creation time, newest first.
     */
    public function findLatestForContext(FeedInterface $feed, string $contextKey): ?FeedContextResultInterface;
}
