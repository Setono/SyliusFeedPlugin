<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\FeedType;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistry;

final class FeedTypeRegistryTest extends TestCase
{
    use ProphecyTrait;

    private function feedType(string $code): FeedTypeInterface
    {
        $feedType = $this->prophesize(FeedTypeInterface::class);
        $feedType->getCode()->willReturn($code);

        return $feedType->reveal();
    }

    /**
     * @test
     */
    public function it_registers_and_retrieves_feed_types_keyed_by_code(): void
    {
        $productVariant = $this->feedType('product_variant');
        $product = $this->feedType('product');

        $registry = new FeedTypeRegistry([$productVariant, $product]);

        self::assertSame($productVariant, $registry->get('product_variant'));
        self::assertSame($product, $registry->get('product'));
        self::assertTrue($registry->has('product'));
        self::assertFalse($registry->has('order'));
        self::assertSame(['product_variant' => $productVariant, 'product' => $product], $registry->all());
    }

    /**
     * @test
     */
    public function it_is_iterable_and_countable(): void
    {
        $productVariant = $this->feedType('product_variant');

        $registry = new FeedTypeRegistry([$productVariant]);

        self::assertCount(1, $registry);
        self::assertSame(['product_variant' => $productVariant], iterator_to_array($registry));
    }

    /**
     * @test
     */
    public function it_throws_when_two_feed_types_share_a_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FeedTypeRegistry([$this->feedType('product'), $this->feedType('product')]);
    }

    /**
     * @test
     */
    public function it_throws_when_retrieving_an_unregistered_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new FeedTypeRegistry([]))->get('missing');
    }
}
