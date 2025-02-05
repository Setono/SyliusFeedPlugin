<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataProvider;

interface DataProviderInterface
{
    /**
     * @param class-string $entity
     * @param list<int> $ids if provided, the returned result will be the objects with these ids
     *
     * @return iterable<int>
     */
    public function getIds(string $entity, array $ids = []): iterable;
}
