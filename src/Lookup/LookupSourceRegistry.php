<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Lookup;

use Setono\SyliusFeedPlugin\Registry\Registry;

/**
 * @extends Registry<LookupSourceInterface>
 */
final class LookupSourceRegistry extends Registry implements LookupSourceRegistryInterface
{
    public function get(string $type): LookupSourceInterface
    {
        return $this->getByKey($type);
    }

    public function has(string $type): bool
    {
        return $this->hasKey($type);
    }

    public function all(): array
    {
        return $this->items();
    }

    /**
     * @param LookupSourceInterface $item
     */
    protected function getKey(object $item): string
    {
        return $item->getType();
    }
}
