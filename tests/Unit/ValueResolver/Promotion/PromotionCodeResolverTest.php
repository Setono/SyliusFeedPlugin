<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Promotion;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Promotion\PromotionCodeResolver;
use Sylius\Component\Promotion\Model\PromotionInterface;

final class PromotionCodeResolverTest extends TestCase
{
    use ProphecyTrait;

    private PromotionCodeResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new PromotionCodeResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('promotion_code', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.promotion_code', $this->resolver->getLabel());
        self::assertSame(FieldType::STRING, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(PromotionInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_promotions_code(): void
    {
        $promotion = $this->prophesize(PromotionInterface::class);
        $promotion->getCode()->willReturn('SUMMER_SALE');

        self::assertSame('SUMMER_SALE', $this->resolver->resolve($promotion->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
