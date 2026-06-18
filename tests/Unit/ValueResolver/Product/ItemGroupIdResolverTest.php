<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Product;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\ValueResolver\Product\ItemGroupIdResolver;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * @covers \Setono\SyliusFeedPlugin\ValueResolver\Product\ItemGroupIdResolver
 */
final class ItemGroupIdResolverTest extends TestCase
{
    use ProphecyTrait;

    private ItemGroupIdResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ItemGroupIdResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('item_group_id', $this->resolver->getName());
        self::assertTrue($this->resolver->supports(ProductVariantInterface::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_parent_product_code(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getCode()->willReturn('PROD-1');

        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getProduct()->willReturn($product->reveal());

        self::assertSame('PROD-1', $this->resolver->resolve($variant->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
