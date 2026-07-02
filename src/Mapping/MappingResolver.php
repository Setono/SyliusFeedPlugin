<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Mapping;

use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetRegistryInterface;
use Setono\SyliusFeedPlugin\Model\FeedFieldInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;

/**
 * @see MappingResolverInterface
 */
final class MappingResolver implements MappingResolverInterface
{
    public function __construct(private readonly MappingPresetRegistryInterface $mappingPresetRegistry)
    {
    }

    public function resolve(FeedInterface $feed, FeedSourceInterface $source): array
    {
        $fields = $source->getFields()->toArray();
        if ([] !== $fields) {
            usort(
                $fields,
                static fn (FeedFieldInterface $a, FeedFieldInterface $b): int => ($a->getPosition() ?? 0) <=> ($b->getPosition() ?? 0),
            );

            return array_map(FieldMapping::fromFeedField(...), $fields);
        }

        foreach ($this->mappingPresetRegistry->forFeedType((string) $source->getFeedType()) as $preset) {
            if ($preset->getFormat() === $feed->getFormat()) {
                return $preset->getMapping();
            }
        }

        return [];
    }
}
