<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver;

/**
 * @extends \IteratorAggregate<string, ValueResolverInterface>
 */
interface ValueResolverRegistryInterface extends \IteratorAggregate
{
    public function get(string $name): ValueResolverInterface;

    public function has(string $name): bool;

    /**
     * @return array<string, ValueResolverInterface>
     */
    public function all(): array;
}
