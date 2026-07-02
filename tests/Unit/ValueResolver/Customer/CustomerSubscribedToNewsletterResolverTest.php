<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Customer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Customer\CustomerSubscribedToNewsletterResolver;
use Sylius\Component\Customer\Model\CustomerInterface;

final class CustomerSubscribedToNewsletterResolverTest extends TestCase
{
    use ProphecyTrait;

    private CustomerSubscribedToNewsletterResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new CustomerSubscribedToNewsletterResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('subscribed_to_newsletter', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.subscribed_to_newsletter', $this->resolver->getLabel());
        self::assertSame(FieldType::BOOL, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(CustomerInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_whether_the_customer_is_subscribed(): void
    {
        $customer = $this->prophesize(CustomerInterface::class);
        $customer->isSubscribedToNewsletter()->willReturn(true);

        self::assertTrue($this->resolver->resolve($customer->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
