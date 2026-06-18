<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Context;

use Setono\SyliusFeedPlugin\Model\FeedInterface;

interface ContextFactoryInterface
{
    /**
     * Expands a feed into the contexts it generates — the cartesian product over the union of its
     * sources' scope dimensions (§6.2).
     *
     * @return list<FeedContext>
     */
    public function create(FeedInterface $feed): array;
}
