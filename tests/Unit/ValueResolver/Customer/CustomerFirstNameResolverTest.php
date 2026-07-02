<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Customer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Customer\CustomerFirstNameResolver;
use Sylius\Component\Customer\Model\CustomerInterface;

final class CustomerFirstNameResolverTest extends TestCase
{
    use ProphecyTrait;

    private CustomerFirstNameResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new CustomerFirstNameResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('first_name', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.first_name', $this->resolver->getLabel());
        self::assertSame(FieldType::STRING, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(CustomerInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_customers_first_name(): void
    {
        $customer = $this->prophesize(CustomerInterface::class);
        $customer->getFirstName()->willReturn('Jane');

        self::assertSame('Jane', $this->resolver->resolve($customer->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
