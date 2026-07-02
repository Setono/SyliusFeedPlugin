<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Promotion;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Promotion\PromotionCouponBasedResolver;
use Sylius\Component\Promotion\Model\PromotionInterface;

final class PromotionCouponBasedResolverTest extends TestCase
{
    use ProphecyTrait;

    private PromotionCouponBasedResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new PromotionCouponBasedResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('coupon_based', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.coupon_based', $this->resolver->getLabel());
        self::assertSame(FieldType::BOOL, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(PromotionInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_whether_the_promotion_is_coupon_based(): void
    {
        $promotion = $this->prophesize(PromotionInterface::class);
        $promotion->isCouponBased()->willReturn(true);

        self::assertTrue($this->resolver->resolve($promotion->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
