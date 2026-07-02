<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\ProductReview;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ProductReview\ReviewCreatedAtResolver;
use Sylius\Component\Review\Model\ReviewInterface;

final class ReviewCreatedAtResolverTest extends TestCase
{
    use ProphecyTrait;

    private ReviewCreatedAtResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ReviewCreatedAtResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('review_created_at', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.review_created_at', $this->resolver->getLabel());
        self::assertSame(FieldType::DATE, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(ReviewInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_created_at_date(): void
    {
        $date = new \DateTimeImmutable('2026-01-01 12:00:00');

        $review = $this->prophesize(ReviewInterface::class);
        $review->getCreatedAt()->willReturn($date);

        self::assertSame($date, $this->resolver->resolve($review->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
