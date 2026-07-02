<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Order;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Order\OrderTotalResolver;
use Sylius\Component\Core\Model\OrderInterface;

final class OrderTotalResolverTest extends TestCase
{
    use ProphecyTrait;

    private OrderTotalResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new OrderTotalResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('total', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.total', $this->resolver->getLabel());
        self::assertSame(FieldType::MONEY, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(OrderInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_order_total_in_minor_units(): void
    {
        $order = $this->prophesize(OrderInterface::class);
        $order->getTotal()->willReturn(999);

        self::assertSame(999, $this->resolver->resolve($order->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
