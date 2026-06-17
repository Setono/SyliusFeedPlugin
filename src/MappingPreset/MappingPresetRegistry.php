<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MappingPreset;

use Webmozart\Assert\Assert;

final class MappingPresetRegistry implements MappingPresetRegistryInterface
{
    /** @var array<string, MappingPresetInterface> */
    private array $presets = [];

    /**
     * @param iterable<MappingPresetInterface> $presets
     */
    public function __construct(iterable $presets)
    {
        foreach ($presets as $preset) {
            $code = $preset->getCode();
            Assert::keyNotExists($this->presets, $code, sprintf('A mapping preset with code "%s" is already registered', $code));

            $this->presets[$code] = $preset;
        }
    }

    public function get(string $code): MappingPresetInterface
    {
        Assert::keyExists($this->presets, $code, sprintf('No mapping preset with code "%s" is registered', $code));

        return $this->presets[$code];
    }

    public function has(string $code): bool
    {
        return isset($this->presets[$code]);
    }

    public function all(): array
    {
        return $this->presets;
    }

    public function forFeedType(string $feedType): array
    {
        return array_values(array_filter(
            $this->presets,
            static fn (MappingPresetInterface $preset): bool => $preset->supports($feedType),
        ));
    }
}
