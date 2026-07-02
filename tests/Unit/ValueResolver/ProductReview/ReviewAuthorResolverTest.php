<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\ProductReview;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ProductReview\ReviewAuthorResolver;
use Sylius\Component\Review\Model\ReviewerInterface;
use Sylius\Component\Review\Model\ReviewInterface;

final class ReviewAuthorResolverTest extends TestCase
{
    use ProphecyTrait;

    private ReviewAuthorResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ReviewAuthorResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('author', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.author', $this->resolver->getLabel());
        self::assertSame(FieldType::STRING, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(ReviewInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_authors_full_name(): void
    {
        $author = $this->prophesize(ReviewerInterface::class);
        $author->getFirstName()->willReturn('Jane');
        $author->getLastName()->willReturn('Doe');

        $review = $this->prophesize(ReviewInterface::class);
        $review->getAuthor()->willReturn($author->reveal());

        self::assertSame('Jane Doe', $this->resolver->resolve($review->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_falls_back_to_the_authors_email_when_no_name_is_set(): void
    {
        $author = $this->prophesize(ReviewerInterface::class);
        $author->getFirstName()->willReturn(null);
        $author->getLastName()->willReturn(null);
        $author->getEmail()->willReturn('reviewer@example.com');

        $review = $this->prophesize(ReviewInterface::class);
        $review->getAuthor()->willReturn($author->reveal());

        self::assertSame('reviewer@example.com', $this->resolver->resolve($review->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_review_has_no_author(): void
    {
        $review = $this->prophesize(ReviewInterface::class);
        $review->getAuthor()->willReturn(null);

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
