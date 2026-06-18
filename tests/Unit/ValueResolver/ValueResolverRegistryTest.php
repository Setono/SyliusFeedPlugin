<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverRegistry;

/**
 * @covers \Setono\SyliusFeedPlugin\ValueResolver\ValueResolverRegistry
 */
final class ValueResolverRegistryTest extends TestCase
{
    use ProphecyTrait;

    private function resolver(string $name): ValueResolverInterface
    {
        $resolver = $this->prophesize(ValueResolverInterface::class);
        $resolver->getName()->willReturn($name);

        return $resolver->reveal();
    }

    /**
     * @test
     */
    public function it_registers_and_retrieves_resolvers_keyed_by_name(): void
    {
        $price = $this->resolver('channel_price');

        $registry = new ValueResolverRegistry([$price]);

        self::assertSame($price, $registry->get('channel_price'));
        self::assertTrue($registry->has('channel_price'));
        self::assertFalse($registry->has('main_image'));
        self::assertSame(['channel_price' => $price], $registry->all());
    }

    /**
     * @test
     */
    public function it_throws_when_two_resolvers_share_a_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ValueResolverRegistry([$this->resolver('id'), $this->resolver('id')]);
    }
}
