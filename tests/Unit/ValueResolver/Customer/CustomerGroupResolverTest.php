<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Customer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Customer\CustomerGroupResolver;
use Sylius\Component\Customer\Model\CustomerGroupInterface;
use Sylius\Component\Customer\Model\CustomerInterface;

final class CustomerGroupResolverTest extends TestCase
{
    use ProphecyTrait;

    private CustomerGroupResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new CustomerGroupResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('group', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.group', $this->resolver->getLabel());
        self::assertSame(FieldType::STRING, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(CustomerInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_customers_group_code(): void
    {
        $group = $this->prophesize(CustomerGroupInterface::class);
        $group->getCode()->willReturn('wholesale');

        $customer = $this->prophesize(CustomerInterface::class);
        $customer->getGroup()->willReturn($group->reveal());

        self::assertSame('wholesale', $this->resolver->resolve($customer->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_customer_has_no_group(): void
    {
        $customer = $this->prophesize(CustomerInterface::class);
        $customer->getGroup()->willReturn(null);

        self::assertNull($this->resolver->resolve($customer->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
