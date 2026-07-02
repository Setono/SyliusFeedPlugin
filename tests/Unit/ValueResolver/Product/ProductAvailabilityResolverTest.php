<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Product;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\Google\Availability;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Product\ProductAvailabilityResolver;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class ProductAvailabilityResolverTest extends TestCase
{
    use ProphecyTrait;

    private ProductAvailabilityResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ProductAvailabilityResolver();
    }

    private function trackedVariant(int $onHand, int $onHold = 0): ProductVariantInterface
    {
        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->isTracked()->willReturn(true);
        $variant->getOnHand()->willReturn($onHand);
        $variant->getOnHold()->willReturn($onHold);

        return $variant->reveal();
    }

    private function untrackedVariant(): ProductVariantInterface
    {
        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->isTracked()->willReturn(false);

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
        self::assertSame('product_availability', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.product_availability', $this->resolver->getLabel());
        self::assertSame(FieldType::STRING, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(ProductInterface::class));
        self::assertFalse($this->resolver->supports(ProductVariantInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_is_in_stock_when_an_untracked_variant_exists(): void
    {
        $product = $this->product([$this->trackedVariant(0), $this->untrackedVariant()]);

        self::assertSame(Availability::IN_STOCK->value, $this->resolver->resolve($product, new FeedContext()));
    }

    /**
     * @test
     */
    public function it_is_in_stock_when_a_tracked_variant_has_available_stock(): void
    {
        $product = $this->product([$this->trackedVariant(0), $this->trackedVariant(5, 2)]);

        self::assertSame(Availability::IN_STOCK->value, $this->resolver->resolve($product, new FeedContext()));
    }

    /**
     * @test
     */
    public function it_is_out_of_stock_when_no_variant_has_available_stock(): void
    {
        $product = $this->product([$this->trackedVariant(0), $this->trackedVariant(3, 3)]);

        self::assertSame(Availability::OUT_OF_STOCK->value, $this->resolver->resolve($product, new FeedContext()));
    }

    /**
     * @test
     */
    public function it_is_out_of_stock_for_a_product_without_variants(): void
    {
        self::assertSame(Availability::OUT_OF_STOCK->value, $this->resolver->resolve($this->product([]), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
