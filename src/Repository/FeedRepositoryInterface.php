<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Repository;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface FeedRepositoryInterface extends RepositoryInterface
{
    public function findOneBySlug(string $slug): ?FeedInterface;
}
