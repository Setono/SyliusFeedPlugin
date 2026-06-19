<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Product;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Currency\ContextCurrencyConverter;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Product\OriginalPriceResolver;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Currency\Converter\CurrencyConverterInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;

/**
 * @covers \Setono\SyliusFeedPlugin\ValueResolver\Product\OriginalPriceResolver
 */
final class OriginalPriceResolverTest extends TestCase
{
    use ProphecyTrait;

    private OriginalPriceResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new OriginalPriceResolver(new ContextCurrencyConverter($this->prophesize(CurrencyConverterInterface::class)->reveal()));
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('original_price', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.original_price', $this->resolver->getLabel());
        self::assertSame(FieldType::MONEY, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(ProductVariantInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_original_price(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $channelPricing = $this->prophesize(ChannelPricingInterface::class);
        $channelPricing->getOriginalPrice()->willReturn(1299);
        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getChannelPricingForChannel($channel)->willReturn($channelPricing->reveal());

        self::assertSame(1299, $this->resolver->resolve($variant->reveal(), new FeedContext($channel)));
    }

    /**
     * @test
     */
    public function it_returns_null_without_a_channel(): void
    {
        $variant = $this->prophesize(ProductVariantInterface::class);

        self::assertNull($this->resolver->resolve($variant->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_converts_the_original_price_to_the_context_currency(): void
    {
        $baseCurrency = $this->prophesize(CurrencyInterface::class);
        $baseCurrency->getCode()->willReturn('USD');

        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getBaseCurrency()->willReturn($baseCurrency->reveal());
        $channel = $channel->reveal();

        $channelPricing = $this->prophesize(ChannelPricingInterface::class);
        $channelPricing->getOriginalPrice()->willReturn(1299);

        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getChannelPricingForChannel($channel)->willReturn($channelPricing->reveal());

        $sylius = $this->prophesize(CurrencyConverterInterface::class);
        $sylius->convert(1299, 'USD', 'EUR')->willReturn(1105);

        $resolver = new OriginalPriceResolver(new ContextCurrencyConverter($sylius->reveal()));

        self::assertSame(1105, $resolver->resolve($variant->reveal(), new FeedContext($channel, null, 'EUR')));
    }
}
