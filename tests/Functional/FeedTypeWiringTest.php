<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional;

use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistry;
use Setono\SyliusFeedPlugin\FeedType\ProductVariantFeedType;
use Setono\SyliusFeedPlugin\Generator\FeedGenerator;
use Setono\SyliusFeedPlugin\MappingPreset\GoogleShoppingMappingPreset;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetRegistry;
use Setono\SyliusFeedPlugin\MappingPreset\MetaMappingPreset;
use Setono\SyliusFeedPlugin\MappingPreset\PartnerAdsMappingPreset;

/**
 * Proves the M1 generation services are wired: the product_variant feed type and Google Shopping
 * preset are collected, and the generator is available.
 */
final class FeedTypeWiringTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_collects_the_product_variant_feed_type(): void
    {
        $registry = self::getContainer()->get(FeedTypeRegistry::class);

        self::assertInstanceOf(FeedTypeRegistry::class, $registry);
        self::assertInstanceOf(ProductVariantFeedType::class, $registry->get('product_variant'));
    }

    /**
     * @test
     */
    public function it_collects_the_google_shopping_preset(): void
    {
        $registry = self::getContainer()->get(MappingPresetRegistry::class);

        self::assertInstanceOf(MappingPresetRegistry::class, $registry);
        self::assertInstanceOf(GoogleShoppingMappingPreset::class, $registry->get('google_shopping'));

        // every product-target preset supports the product_variant feed type
        $presetClasses = array_map(get_class(...), $registry->forFeedType('product_variant'));
        self::assertContains(GoogleShoppingMappingPreset::class, $presetClasses);
        self::assertContains(MetaMappingPreset::class, $presetClasses);
        self::assertContains(PartnerAdsMappingPreset::class, $presetClasses);
    }

    /**
     * @test
     */
    public function it_registers_the_feed_generator(): void
    {
        self::assertInstanceOf(FeedGenerator::class, self::getContainer()->get(FeedGenerator::class));
    }
}
