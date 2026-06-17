<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver;

interface ValueResolverRegistryInterface
{
    public function get(string $name): ValueResolverInterface;

    public function has(string $name): bool;

    /**
     * @return array<string, ValueResolverInterface>
     */
    public function all(): array;
}
