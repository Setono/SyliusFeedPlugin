<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Order;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Order\OrderCustomerEmailResolver;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Customer\Model\CustomerInterface;

final class OrderCustomerEmailResolverTest extends TestCase
{
    use ProphecyTrait;

    private OrderCustomerEmailResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new OrderCustomerEmailResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('customer_email', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.customer_email', $this->resolver->getLabel());
        self::assertSame(FieldType::STRING, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(OrderInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_customers_email(): void
    {
        $customer = $this->prophesize(CustomerInterface::class);
        $customer->getEmail()->willReturn('shopper@example.com');

        $order = $this->prophesize(OrderInterface::class);
        $order->getCustomer()->willReturn($customer->reveal());

        self::assertSame('shopper@example.com', $this->resolver->resolve($order->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_order_has_no_customer(): void
    {
        $order = $this->prophesize(OrderInterface::class);
        $order->getCustomer()->willReturn(null);

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
