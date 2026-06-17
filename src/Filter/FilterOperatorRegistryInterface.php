<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Filter;

interface FilterOperatorRegistryInterface
{
    public function get(string $name): FilterOperatorInterface;

    public function has(string $name): bool;

    /**
     * @return array<string, FilterOperatorInterface>
     */
    public function all(): array;
}
