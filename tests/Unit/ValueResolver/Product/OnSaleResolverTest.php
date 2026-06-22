<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Product;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Product\OnSaleResolver;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class OnSaleResolverTest extends TestCase
{
    use ProphecyTrait;

    private OnSaleResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new OnSaleResolver();
    }

    private function variant(ChannelInterface $channel, ?int $price, ?int $originalPrice): ProductVariantInterface
    {
        $channelPricing = $this->prophesize(ChannelPricingInterface::class);
        $channelPricing->getPrice()->willReturn($price);
        $channelPricing->getOriginalPrice()->willReturn($originalPrice);

        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getChannelPricingForChannel($channel)->willReturn($channelPricing->reveal());

        return $variant->reveal();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('on_sale', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.on_sale', $this->resolver->getLabel());
        self::assertSame(FieldType::BOOL, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(ProductVariantInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_is_true_when_the_original_price_is_higher_than_the_price(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        self::assertTrue($this->resolver->resolve($this->variant($channel, 800, 1000), new FeedContext($channel)));
    }

    /**
     * @test
     */
    public function it_is_false_when_not_discounted(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        self::assertFalse($this->resolver->resolve($this->variant($channel, 1000, 1000), new FeedContext($channel)));
    }

    /**
     * @test
     */
    public function it_is_false_without_a_channel(): void
    {
        self::assertFalse($this->resolver->resolve($this->prophesize(ProductVariantInterface::class)->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_is_false_without_channel_pricing(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getChannelPricingForChannel($channel)->willReturn(null);

        self::assertFalse($this->resolver->resolve($variant->reveal(), new FeedContext($channel)));
    }
}
