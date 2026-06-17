<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

/**
 * @extends \IteratorAggregate<string, TransformationInterface>
 */
interface TransformationRegistryInterface extends \IteratorAggregate
{
    public function get(string $type): TransformationInterface;

    public function has(string $type): bool;

    /**
     * @return array<string, TransformationInterface>
     */
    public function all(): array;
}
