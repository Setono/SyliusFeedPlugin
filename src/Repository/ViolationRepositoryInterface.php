<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Repository;

use Doctrine\ORM\QueryBuilder;
use Setono\SyliusFeedPlugin\DTO\SeverityCount;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\ViolationInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<ViolationInterface>
 */
interface ViolationRepositoryInterface extends RepositoryInterface
{
    /**
     * @param int|FeedInterface $feed
     *
     * @return SeverityCount[]
     */
    public function findCountsGroupedBySeverity($feed = null): array;

    /**
     * @param mixed $feed
     */
    public function createQueryBuilderByFeed($feed): QueryBuilder;
}
