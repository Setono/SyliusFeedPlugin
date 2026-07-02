<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Order;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Order\OrderCheckoutCompletedAtResolver;
use Sylius\Component\Core\Model\OrderInterface;

final class OrderCheckoutCompletedAtResolverTest extends TestCase
{
    use ProphecyTrait;

    private OrderCheckoutCompletedAtResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new OrderCheckoutCompletedAtResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('checkout_completed_at', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.checkout_completed_at', $this->resolver->getLabel());
        self::assertSame(FieldType::DATE, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(OrderInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_checkout_completed_at_date(): void
    {
        $date = new \DateTimeImmutable('2026-01-01 12:00:00');

        $order = $this->prophesize(OrderInterface::class);
        $order->getCheckoutCompletedAt()->willReturn($date);

        self::assertSame($date, $this->resolver->resolve($order->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_when_checkout_was_never_completed(): void
    {
        $order = $this->prophesize(OrderInterface::class);
        $order->getCheckoutCompletedAt()->willReturn(null);

        self::assertNull($this->resolver->resolve($order->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
