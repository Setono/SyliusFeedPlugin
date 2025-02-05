<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Repository;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @extends RepositoryInterface<FeedInterface>
 */
interface FeedRepositoryInterface extends RepositoryInterface
{
    /**
     * @return list<FeedInterface>
     */
    public function findEnabled(): array;
}
