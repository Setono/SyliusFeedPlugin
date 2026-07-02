<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Customer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Customer\CustomerEmailResolver;
use Sylius\Component\Customer\Model\CustomerInterface;

final class CustomerEmailResolverTest extends TestCase
{
    use ProphecyTrait;

    private CustomerEmailResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new CustomerEmailResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('email', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.email', $this->resolver->getLabel());
        self::assertSame(FieldType::STRING, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(CustomerInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_customers_email(): void
    {
        $customer = $this->prophesize(CustomerInterface::class);
        $customer->getEmail()->willReturn('shopper@example.com');

        self::assertSame('shopper@example.com', $this->resolver->resolve($customer->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
