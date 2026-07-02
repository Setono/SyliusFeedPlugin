<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Mapping\FieldMapping;
use Setono\SyliusFeedPlugin\Mapping\MappingResolver;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetInterface;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetRegistryInterface;
use Setono\SyliusFeedPlugin\Model\FeedField;
use Setono\SyliusFeedPlugin\Model\FeedFieldInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;

/**
 * The admin-editable FeedField rows win once a source has any (ordered by position); otherwise the
 * matching MappingPreset is the fallback. This is the single resolution shared by the generator and
 * the preview, so both consume the exact same mappings.
 */
final class MappingResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_hydrates_mappings_from_feed_fields_ordered_by_position(): void
    {
        $source = $this->prophesize(FeedSourceInterface::class);
        $source->getFields()->willReturn(new ArrayCollection([
            $this->feedField('title', 'title', 1),
            $this->feedField('id', 'id', 0),
        ]));

        $presetRegistry = $this->prophesize(MappingPresetRegistryInterface::class);

        $mappings = (new MappingResolver($presetRegistry->reveal()))->resolve(
            $this->prophesize(FeedInterface::class)->reveal(),
            $source->reveal(),
        );

        self::assertCount(2, $mappings);
        self::assertSame('id', $mappings[0]->getOutputField());
        self::assertSame('title', $mappings[1]->getOutputField());
    }

    /**
     * @test
     */
    public function it_falls_back_to_the_matching_preset_when_there_are_no_feed_fields(): void
    {
        $source = $this->prophesize(FeedSourceInterface::class);
        $source->getFields()->willReturn(new ArrayCollection());
        $source->getFeedType()->willReturn('product_variant');

        $feed = $this->prophesize(FeedInterface::class);
        $feed->getFormat()->willReturn('google_rss');

        $expected = [FieldMapping::field('g:id', 'id')];

        $nonMatching = $this->prophesize(MappingPresetInterface::class);
        $nonMatching->getFormat()->willReturn('csv');

        $matching = $this->prophesize(MappingPresetInterface::class);
        $matching->getFormat()->willReturn('google_rss');
        $matching->getMapping()->willReturn($expected);

        $presetRegistry = $this->prophesize(MappingPresetRegistryInterface::class);
        $presetRegistry->forFeedType('product_variant')->willReturn([$nonMatching->reveal(), $matching->reveal()]);

        $mappings = (new MappingResolver($presetRegistry->reveal()))->resolve($feed->reveal(), $source->reveal());

        self::assertSame($expected, $mappings);
    }

    /**
     * @test
     */
    public function it_returns_no_mappings_when_neither_fields_nor_a_matching_preset_exist(): void
    {
        $source = $this->prophesize(FeedSourceInterface::class);
        $source->getFields()->willReturn(new ArrayCollection());
        $source->getFeedType()->willReturn('product_variant');

        $feed = $this->prophesize(FeedInterface::class);
        $feed->getFormat()->willReturn('google_rss');

        $presetRegistry = $this->prophesize(MappingPresetRegistryInterface::class);
        $presetRegistry->forFeedType('product_variant')->willReturn([]);

        $mappings = (new MappingResolver($presetRegistry->reveal()))->resolve($feed->reveal(), $source->reveal());

        self::assertSame([], $mappings);
    }

    private function feedField(string $outputField, string $sourceField, int $position): FeedFieldInterface
    {
        $field = new FeedField();
        $field->setOutputField($outputField);
        $field->setSourceType('field');
        $field->setSourceValue($sourceField);
        $field->setPosition($position);

        return $field;
    }
}
