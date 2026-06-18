<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Product;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\ValueResolver\Product\AvailabilityResolver;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * @covers \Setono\SyliusFeedPlugin\ValueResolver\Product\AvailabilityResolver
 */
final class AvailabilityResolverTest extends TestCase
{
    use ProphecyTrait;

    private AvailabilityResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new AvailabilityResolver();
    }

    private function variant(bool $tracked, int $onHand = 0, int $onHold = 0): ProductVariantInterface
    {
        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->isTracked()->willReturn($tracked);
        $variant->getOnHand()->willReturn($onHand);
        $variant->getOnHold()->willReturn($onHold);

        return $variant->reveal();
    }

    /**
     * @test
     */
    public function it_reports_untracked_variants_as_in_stock(): void
    {
        self::assertSame('in_stock', $this->resolver->resolve($this->variant(false), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_reports_a_tracked_variant_with_available_stock_as_in_stock(): void
    {
        self::assertSame('in_stock', $this->resolver->resolve($this->variant(true, 5, 2), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_reports_a_tracked_variant_without_available_stock_as_out_of_stock(): void
    {
        self::assertSame('out_of_stock', $this->resolver->resolve($this->variant(true, 2, 2), new FeedContext()));
    }
}
