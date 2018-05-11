<?php

declare(strict_types=1);

namespace Loevgaard\SyliusFeedPlugin\Repository;

use Loevgaard\SyliusFeedPlugin\Entity\FeedInterface;

interface FeedRepositoryInterface
{
    public function findByUuid(string $uuid): ?FeedInterface;
}