<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Repository;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<FeedInterface>
 */
interface FeedRepositoryInterface extends RepositoryInterface
{
    /**
     * Atomically increments the completed-context counter and returns the new value, so that
     * exactly one concurrent generation run observes the value that reaches the total (§6.3).
     */
    public function incrementCompletedContexts(FeedInterface $feed): int;
}
