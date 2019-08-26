<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Repository;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface FeedRepositoryInterface extends RepositoryInterface
{
    public function findOneByUuid(string $uuid): ?FeedInterface;

    /**
     * Returns all enabled feeds
     *
     * @return FeedInterface[]
     */
    public function findEnabled(): array;

    /**
     * Increments the finished batches count by 1
     */
    public function incrementFinishedBatches(FeedInterface $feed): void;
}
