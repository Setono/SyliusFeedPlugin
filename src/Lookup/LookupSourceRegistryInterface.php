<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Lookup;

interface LookupSourceRegistryInterface
{
    public function get(string $type): LookupSourceInterface;

    public function has(string $type): bool;

    /**
     * @return array<string, LookupSourceInterface>
     */
    public function all(): array;
}
