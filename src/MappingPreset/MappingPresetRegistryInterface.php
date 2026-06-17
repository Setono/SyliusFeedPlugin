<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MappingPreset;

/**
 * @extends \IteratorAggregate<string, MappingPresetInterface>
 */
interface MappingPresetRegistryInterface extends \IteratorAggregate
{
    public function get(string $code): MappingPresetInterface;

    public function has(string $code): bool;

    /**
     * @return array<string, MappingPresetInterface>
     */
    public function all(): array;

    /**
     * All presets that support the given feed type code (drives the admin target picker).
     *
     * @return list<MappingPresetInterface>
     */
    public function forFeedType(string $feedType): array;
}
