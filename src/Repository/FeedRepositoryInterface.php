<?php

declare(strict_types=1);

namespace Loevgaard\SyliusFeedPlugin\Repository;

use Loevgaard\SyliusFeedPlugin\Entity\FeedInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

interface FeedRepositoryInterface extends RepositoryInterface
{
    public function findByUuid(string $uuid): ?FeedInterface;
}