<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

/**
 * @extends \IteratorAggregate<string, OperatorInterface>
 */
interface OperatorRegistryInterface extends \IteratorAggregate
{
    public function get(string $name): OperatorInterface;

    public function has(string $name): bool;

    /**
     * @return array<string, OperatorInterface>
     */
    public function all(): array;
}
