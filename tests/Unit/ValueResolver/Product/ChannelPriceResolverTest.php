<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Product;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Product\ChannelPriceResolver;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * @covers \Setono\SyliusFeedPlugin\ValueResolver\Product\ChannelPriceResolver
 */
final class ChannelPriceResolverTest extends TestCase
{
    use ProphecyTrait;

    private ChannelPriceResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ChannelPriceResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('channel_price', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.channel_price', $this->resolver->getLabel());
        self::assertSame(FieldType::MONEY, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(ProductVariantInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_channel_price_in_minor_units(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        $channelPricing = $this->prophesize(ChannelPricingInterface::class);
        $channelPricing->getPrice()->willReturn(999);

        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getChannelPricingForChannel($channel)->willReturn($channelPricing->reveal());

        self::assertSame(999, $this->resolver->resolve($variant->reveal(), new FeedContext($channel)));
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_context_has_no_channel(): void
    {
        $variant = $this->prophesize(ProductVariantInterface::class);

        self::assertNull($this->resolver->resolve($variant->reveal(), new FeedContext()));
    }
}
