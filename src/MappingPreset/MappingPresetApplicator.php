<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MappingPreset;

use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Model\FeedField;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSource;

/**
 * @see MappingPresetApplicatorInterface
 */
final class MappingPresetApplicator implements MappingPresetApplicatorInterface
{
    public function __construct(private readonly FeedTypeRegistryInterface $feedTypeRegistry)
    {
    }

    public function apply(FeedInterface $feed, MappingPresetInterface $preset): void
    {
        $feed->setFormat($preset->getFormat());

        $source = new FeedSource();
        $source->setFeedType($this->resolveFeedType($preset));
        $source->setPosition($feed->getSources()->count());

        $position = 0;
        foreach ($preset->getMapping() as $mapping) {
            $field = new FeedField();
            $mapping->writeTo($field);
            $field->setPosition($position);
            $source->addField($field);
            ++$position;
        }

        $feed->addSource($source);
    }

    /**
     * The concrete feed type to iterate — the first registered type the preset supports.
     */
    private function resolveFeedType(MappingPresetInterface $preset): string
    {
        foreach ($this->feedTypeRegistry->all() as $feedType) {
            if ($preset->supports($feedType->getCode())) {
                return $feedType->getCode();
            }
        }

        throw new \RuntimeException(sprintf(
            'No registered feed type is supported by the "%s" mapping preset',
            $preset->getCode(),
        ));
    }
}
