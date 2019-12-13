<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Repository;

use Doctrine\ORM\QueryBuilder;
use Setono\SyliusFeedPlugin\DTO\SeverityCount;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface ViolationRepositoryInterface extends RepositoryInterface
{
    /**
     * @return SeverityCount[]
     */
    public function findCountsGroupedBySeverity(): array;

    /**
     * @param mixed $feed
     */
    public function createQueryBuilderByFeed($feed): QueryBuilder;
}
