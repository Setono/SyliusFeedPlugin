<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Specification\Registry;

use Setono\SyliusFeedPlugin\Specification\Specification;

/**
 * @extends \IteratorAggregate<int, class-string<Specification>>
 */
interface SpecificationRegistryInterface extends \IteratorAggregate, \Countable
{
    /**
     * @param class-string<Specification> $specification
     *
     * @throws \InvalidArgumentException if the specification already exists
     */
    public function add(string $specification): void;

    public function has(string $specification): bool;

    /**
     * @return list<class-string<Specification>>
     */
    public function all(): array;
}
