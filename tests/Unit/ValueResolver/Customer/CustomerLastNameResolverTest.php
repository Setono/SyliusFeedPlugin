<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Customer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Customer\CustomerLastNameResolver;
use Sylius\Component\Customer\Model\CustomerInterface;

final class CustomerLastNameResolverTest extends TestCase
{
    use ProphecyTrait;

    private CustomerLastNameResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new CustomerLastNameResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('last_name', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.last_name', $this->resolver->getLabel());
        self::assertSame(FieldType::STRING, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(CustomerInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_customers_last_name(): void
    {
        $customer = $this->prophesize(CustomerInterface::class);
        $customer->getLastName()->willReturn('Doe');

        self::assertSame('Doe', $this->resolver->resolve($customer->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
