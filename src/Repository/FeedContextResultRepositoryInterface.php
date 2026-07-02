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
     * The most recent result recorded for the given feed and context key (§11) — the row the
     * publish gate consults for the previously served output. Ordered by creation time, newest first.
     */
    public function findLatestPublished(FeedInterface $feed, string $contextKey): ?FeedContextResultInterface;
}
