<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataProvider;

interface DataProviderInterface
{
    /**
     * @param class-string $entity
     *
     * @return iterable<int>
     */
    public function getIds(string $entity): iterable;

    /**
     * @template T
     *
     * @param class-string<T> $entity
     * @param list<int> $ids
     *
     * @return iterable<T>
     */
    public function getObjects(string $entity, array $ids): iterable;
}
