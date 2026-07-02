<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Promotion;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Promotion\PromotionStartsAtResolver;
use Sylius\Component\Promotion\Model\PromotionInterface;

final class PromotionStartsAtResolverTest extends TestCase
{
    use ProphecyTrait;

    private PromotionStartsAtResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new PromotionStartsAtResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('starts_at', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.starts_at', $this->resolver->getLabel());
        self::assertSame(FieldType::DATE, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(PromotionInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_starts_at_date(): void
    {
        $date = new \DateTimeImmutable('2026-01-01 12:00:00');

        $promotion = $this->prophesize(PromotionInterface::class);
        $promotion->getStartsAt()->willReturn($date);

        self::assertSame($date, $this->resolver->resolve($promotion->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_promotion_has_no_start_date(): void
    {
        $promotion = $this->prophesize(PromotionInterface::class);
        $promotion->getStartsAt()->willReturn(null);

        self::assertNull($this->resolver->resolve($promotion->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
