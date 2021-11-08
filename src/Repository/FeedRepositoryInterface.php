<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Repository;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface FeedRepositoryInterface extends RepositoryInterface
{
    public function findOneByCode(string $code): ?FeedInterface;

    /**
     * Returns all feed that are ready to be processed feeds
     *
     * @return FeedInterface[]
     */
    public function findReadyToBeProcessed(): array;

    /**
     * Increments the finished batches count by 1
     */
    public function incrementFinishedBatches(FeedInterface $feed): void;

    /**
     * Returns true if all batches for the given feed has been generated
     */
    public function batchesGenerated(FeedInterface $feed): bool;
}
