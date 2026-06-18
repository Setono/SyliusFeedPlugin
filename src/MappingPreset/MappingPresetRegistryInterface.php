<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MappingPreset;

use Setono\SyliusFeedPlugin\Registry\RegistryInterface;

/**
 * @extends RegistryInterface<MappingPresetInterface>
 */
interface MappingPresetRegistryInterface extends RegistryInterface
{
    public function get(string $code): MappingPresetInterface;

    /**
     * All presets that support the given feed type code (drives the admin target picker).
     *
     * @return list<MappingPresetInterface>
     */
    public function forFeedType(string $feedType): array;
}
