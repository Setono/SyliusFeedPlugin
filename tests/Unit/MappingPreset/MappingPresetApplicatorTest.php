<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\MappingPreset;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\MappingPreset\GoogleShoppingMappingPreset;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetApplicator;
use Setono\SyliusFeedPlugin\Model\Feed;

final class MappingPresetApplicatorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_seeds_a_feed_from_a_preset(): void
    {
        $feedType = $this->prophesize(FeedTypeInterface::class);
        $feedType->getCode()->willReturn('product_variant');

        $feedTypeRegistry = $this->prophesize(FeedTypeRegistryInterface::class);
        $feedTypeRegistry->all()->willReturn(['product_variant' => $feedType->reveal()]);

        $applicator = new MappingPresetApplicator($feedTypeRegistry->reveal());
        $preset = new GoogleShoppingMappingPreset();

        $feed = new Feed();
        $applicator->apply($feed, $preset);

        self::assertSame('google_rss', $feed->getFormat());

        $sources = $feed->getSources()->getValues();
        self::assertCount(1, $sources);

        $source = $sources[0];
        self::assertSame('product_variant', $source->getFeedType());

        $fields = $source->getFields()->getValues();
        self::assertCount(\count($preset->getMapping()), $fields);
        self::assertCount(13, $fields);

        $requiresInputByOutput = [];
        foreach ($fields as $field) {
            $requiresInputByOutput[$field->getOutputField()] = $field->getRequiresInput();
        }

        self::assertTrue($requiresInputByOutput['g:brand']);
        self::assertTrue($requiresInputByOutput['g:gtin']);
        self::assertTrue($requiresInputByOutput['g:google_product_category']);
        self::assertFalse($requiresInputByOutput['g:id']);
        self::assertFalse($requiresInputByOutput['g:title']);
    }
}
