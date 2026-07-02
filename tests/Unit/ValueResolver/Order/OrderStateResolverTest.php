<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Order;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Order\OrderStateResolver;
use Sylius\Component\Core\Model\OrderInterface;

final class OrderStateResolverTest extends TestCase
{
    use ProphecyTrait;

    private OrderStateResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new OrderStateResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('state', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.state', $this->resolver->getLabel());
        self::assertSame(FieldType::STRING, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(OrderInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_order_state(): void
    {
        $order = $this->prophesize(OrderInterface::class);
        $order->getState()->willReturn('new');

        self::assertSame('new', $this->resolver->resolve($order->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
