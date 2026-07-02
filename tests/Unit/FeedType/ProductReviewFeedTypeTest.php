<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\FeedType;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\DataSource\DataSourceInterface;
use Setono\SyliusFeedPlugin\FeedType\ProductReviewFeedType;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\Mapping\ScopeDimension;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverRegistryInterface;

final class ProductReviewFeedTypeTest extends TestCase
{
    use ProphecyTrait;

    private DataSourceInterface $dataSource;

    private ProductReviewFeedType $feedType;

    protected function setUp(): void
    {
        $this->dataSource = $this->prophesize(DataSourceInterface::class)->reveal();

        $resolver = $this->prophesize(ValueResolverInterface::class);
        $resolver->getLabel()->willReturn('label');
        $resolver->getType()->willReturn(FieldType::STRING);

        $registry = $this->prophesize(ValueResolverRegistryInterface::class);
        $registry->has(\Prophecy\Argument::type('string'))->willReturn(true);
        $registry->get(\Prophecy\Argument::type('string'))->willReturn($resolver->reveal());

        $this->feedType = new ProductReviewFeedType($this->dataSource, $registry->reveal());
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('product_review', $this->feedType->getCode());
        self::assertSame('setono_sylius_feed.feed_type.product_review', $this->feedType->getLabel());
        self::assertSame($this->dataSource, $this->feedType->getDataSource());
        self::assertSame([ScopeDimension::CHANNEL, ScopeDimension::LOCALE], $this->feedType->getScopeDimensions());
    }

    /**
     * @test
     */
    public function it_exposes_its_available_source_fields(): void
    {
        $fields = $this->feedType->getAvailableFields();

        foreach (['rating', 'comment', 'author', 'product_id', 'review_created_at', 'status'] as $name) {
            self::assertArrayHasKey($name, $fields);
            self::assertSame($name, $fields[$name]->getName());
        }
    }

    /**
     * @test
     */
    public function it_omits_fields_with_no_registered_resolver(): void
    {
        $registry = $this->prophesize(ValueResolverRegistryInterface::class);
        $registry->has(\Prophecy\Argument::type('string'))->willReturn(false);

        $feedType = new ProductReviewFeedType($this->dataSource, $registry->reveal());

        self::assertSame([], $feedType->getAvailableFields());
    }
}
