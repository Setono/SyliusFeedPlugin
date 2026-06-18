<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional;

use Setono\SyliusFeedPlugin\ValueResolver\Product\AvailabilityResolver;
use Setono\SyliusFeedPlugin\ValueResolver\Product\ChannelPriceResolver;
use Setono\SyliusFeedPlugin\ValueResolver\Product\IdResolver;
use Setono\SyliusFeedPlugin\ValueResolver\Product\ItemGroupIdResolver;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverRegistryInterface;

/**
 * Proves the product value resolvers are auto-tagged and collected into the registry through the
 * DI prototype + `_instanceof` autoconfiguration.
 */
final class ValueResolverWiringTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_collects_the_product_value_resolvers(): void
    {
        $registry = self::getContainer()->get('setono_sylius_feed.registry.value_resolver');

        self::assertInstanceOf(ValueResolverRegistryInterface::class, $registry);
        self::assertInstanceOf(IdResolver::class, $registry->get('id'));
        self::assertInstanceOf(ItemGroupIdResolver::class, $registry->get('item_group_id'));
        self::assertInstanceOf(ChannelPriceResolver::class, $registry->get('channel_price'));
        self::assertInstanceOf(AvailabilityResolver::class, $registry->get('availability'));
    }
}
