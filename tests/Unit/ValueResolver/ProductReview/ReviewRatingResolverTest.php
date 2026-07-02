<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\ProductReview;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ProductReview\ReviewRatingResolver;
use Sylius\Component\Review\Model\ReviewInterface;

final class ReviewRatingResolverTest extends TestCase
{
    use ProphecyTrait;

    private ReviewRatingResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ReviewRatingResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('rating', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.rating', $this->resolver->getLabel());
        self::assertSame(FieldType::INTEGER, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(ReviewInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_reviews_rating(): void
    {
        $review = $this->prophesize(ReviewInterface::class);
        $review->getRating()->willReturn(5);

        self::assertSame(5, $this->resolver->resolve($review->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
