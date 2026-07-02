<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Product;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Currency\ContextCurrencyConverter;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Product\FromPriceResolver;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Currency\Converter\CurrencyConverterInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;

final class FromPriceResolverTest extends TestCase
{
    use ProphecyTrait;

    private FromPriceResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new FromPriceResolver(new ContextCurrencyConverter($this->prophesize(CurrencyConverterInterface::class)->reveal()));
    }

    private function variant(ChannelInterface $channel, ?int $price, bool $enabled = true): ProductVariantInterface
    {
        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->isEnabled()->willReturn($enabled);

        $channelPricing = null;
        if (null !== $price) {
            $pricing = $this->prophesize(ChannelPricingInterface::class);
            $pricing->getPrice()->willReturn($price);
            $channelPricing = $pricing->reveal();
        }

        $variant->getChannelPricingForChannel($channel)->willReturn($channelPricing);

        return $variant->reveal();
    }

    /**
     * @param list<ProductVariantInterface> $variants
     */
    private function product(array $variants): ProductInterface
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getVariants()->willReturn(new ArrayCollection($variants));

        return $product->reveal();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('from_price', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.from_price', $this->resolver->getLabel());
        self::assertSame(FieldType::MONEY, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(ProductInterface::class));
        self::assertFalse($this->resolver->supports(ProductVariantInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_cheapest_enabled_variant_price(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $product = $this->product([
            $this->variant($channel, 1500),
            $this->variant($channel, 999),
            $this->variant($channel, 1299),
        ]);

        self::assertSame(999, $this->resolver->resolve($product, new FeedContext($channel)));
    }

    /**
     * @test
     */
    public function it_ignores_disabled_variants(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $product = $this->product([
            $this->variant($channel, 1500),
            $this->variant($channel, 500, enabled: false),
        ]);

        self::assertSame(1500, $this->resolver->resolve($product, new FeedContext($channel)));
    }

    /**
     * @test
     */
    public function it_returns_null_when_no_enabled_variant_has_a_price(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();
        $product = $this->product([
            $this->variant($channel, null),
            $this->variant($channel, 700, enabled: false),
        ]);

        self::assertNull($this->resolver->resolve($product, new FeedContext($channel)));
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_context_has_no_channel(): void
    {
        self::assertNull($this->resolver->resolve($this->product([]), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext($channel)));
    }

    /**
     * @test
     */
    public function it_converts_the_price_to_the_context_currency(): void
    {
        $baseCurrency = $this->prophesize(CurrencyInterface::class);
        $baseCurrency->getCode()->willReturn('USD');

        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getBaseCurrency()->willReturn($baseCurrency->reveal());
        $channel = $channel->reveal();

        $product = $this->product([$this->variant($channel, 999)]);

        $sylius = $this->prophesize(CurrencyConverterInterface::class);
        $sylius->convert(999, 'USD', 'EUR')->willReturn(850);

        $resolver = new FromPriceResolver(new ContextCurrencyConverter($sylius->reveal()));

        self::assertSame(850, $resolver->resolve($product, new FeedContext($channel, null, 'EUR')));
    }
}
