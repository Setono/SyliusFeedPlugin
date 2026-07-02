<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Customer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Customer\CustomerCreatedAtResolver;
use Sylius\Component\Customer\Model\CustomerInterface;

final class CustomerCreatedAtResolverTest extends TestCase
{
    use ProphecyTrait;

    private CustomerCreatedAtResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new CustomerCreatedAtResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('created_at', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.created_at', $this->resolver->getLabel());
        self::assertSame(FieldType::DATE, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(CustomerInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_created_at_date(): void
    {
        $date = new \DateTimeImmutable('2026-01-01 12:00:00');

        $customer = $this->prophesize(CustomerInterface::class);
        $customer->getCreatedAt()->willReturn($date);

        self::assertSame($date, $this->resolver->resolve($customer->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
