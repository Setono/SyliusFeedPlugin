<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MappingPreset;

use Setono\SyliusFeedPlugin\Registry\Registry;

/**
 * @extends Registry<MappingPresetInterface>
 */
final class MappingPresetRegistry extends Registry implements MappingPresetRegistryInterface
{
    public function get(string $code): MappingPresetInterface
    {
        return $this->getByKey($code);
    }

    public function has(string $code): bool
    {
        return $this->hasKey($code);
    }

    public function all(): array
    {
        return $this->items();
    }

    public function forFeedType(string $feedType): array
    {
        return array_values(array_filter(
            $this->items(),
            static fn (MappingPresetInterface $preset): bool => $preset->supports($feedType),
        ));
    }

    /**
     * @param MappingPresetInterface $item
     */
    protected function getKey(object $item): string
    {
        return $item->getCode();
    }
}
