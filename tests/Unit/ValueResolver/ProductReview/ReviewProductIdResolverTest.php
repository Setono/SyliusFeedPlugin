<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\ProductReview;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ProductReview\ReviewProductIdResolver;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Review\Model\ReviewInterface;

final class ReviewProductIdResolverTest extends TestCase
{
    use ProphecyTrait;

    private ReviewProductIdResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ReviewProductIdResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('product_id', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.product_id', $this->resolver->getLabel());
        self::assertSame(FieldType::STRING, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(ReviewInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_reviewed_products_code(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getCode()->willReturn('PROD-1');

        $review = $this->prophesize(ReviewInterface::class);
        $review->getReviewSubject()->willReturn($product->reveal());

        self::assertSame('PROD-1', $this->resolver->resolve($review->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_subject_is_not_a_product(): void
    {
        $review = $this->prophesize(ReviewInterface::class);
        $review->getReviewSubject()->willReturn(null);

        self::assertNull($this->resolver->resolve($review->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
