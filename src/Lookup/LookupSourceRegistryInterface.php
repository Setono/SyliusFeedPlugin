<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Lookup;

/**
 * @extends \IteratorAggregate<string, LookupSourceInterface>
 */
interface LookupSourceRegistryInterface extends \IteratorAggregate
{
    public function get(string $type): LookupSourceInterface;

    public function has(string $type): bool;

    /**
     * @return array<string, LookupSourceInterface>
     */
    public function all(): array;
}
