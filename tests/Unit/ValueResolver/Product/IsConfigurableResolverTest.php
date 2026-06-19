<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Product;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Product\IsConfigurableResolver;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * @covers \Setono\SyliusFeedPlugin\ValueResolver\Product\IsConfigurableResolver
 */
final class IsConfigurableResolverTest extends TestCase
{
    use ProphecyTrait;

    private IsConfigurableResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new IsConfigurableResolver();
    }

    private function variantWithVariantCount(int $count): ProductVariantInterface
    {
        $variant = $this->prophesize(ProductVariantInterface::class);

        $product = $this->prophesize(ProductInterface::class);
        $product->getVariants()->willReturn(new ArrayCollection(array_fill(0, $count, $variant->reveal())));
        $variant->getProduct()->willReturn($product->reveal());

        return $variant->reveal();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('is_configurable', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.is_configurable', $this->resolver->getLabel());
        self::assertSame(FieldType::BOOL, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(ProductVariantInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_is_true_for_a_multi_variant_product(): void
    {
        self::assertTrue($this->resolver->resolve($this->variantWithVariantCount(2), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_is_false_for_a_single_variant_product(): void
    {
        self::assertFalse($this->resolver->resolve($this->variantWithVariantCount(1), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
