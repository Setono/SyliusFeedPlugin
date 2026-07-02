<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Product;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Product\VariantCountResolver;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class VariantCountResolverTest extends TestCase
{
    use ProphecyTrait;

    private VariantCountResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new VariantCountResolver();
    }

    private function productWithVariantCount(int $count): ProductInterface
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getVariants()->willReturn(new ArrayCollection(array_fill(0, $count, $this->prophesize(ProductVariantInterface::class)->reveal())));

        return $product->reveal();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('variant_count', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.variant_count', $this->resolver->getLabel());
        self::assertSame(FieldType::INTEGER, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(ProductVariantInterface::class));
        self::assertTrue($this->resolver->supports(ProductInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_counts_the_variants_of_a_product_entity(): void
    {
        self::assertSame(3, $this->resolver->resolve($this->productWithVariantCount(3), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_counts_the_variants_of_a_products_parent_via_a_variant_entity(): void
    {
        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getProduct()->willReturn($this->productWithVariantCount(2));

        self::assertSame(2, $this->resolver->resolve($variant->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
