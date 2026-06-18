<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Model\FeedField;
use Setono\SyliusFeedPlugin\Model\FeedFilter;
use Setono\SyliusFeedPlugin\Model\FeedSource;

/**
 * @covers \Setono\SyliusFeedPlugin\Model\FeedSource
 */
final class FeedSourceTest extends TestCase
{
    /**
     * @test
     */
    public function it_starts_with_empty_field_and_filter_collections(): void
    {
        $source = new FeedSource();

        self::assertNull($source->getId());
        self::assertCount(0, $source->getFields());
        self::assertCount(0, $source->getFilters());
    }

    /**
     * @test
     */
    public function it_holds_its_feed_type_and_position(): void
    {
        $source = new FeedSource();
        $source->setFeedType('product_variant');
        $source->setPosition(1);

        self::assertSame('product_variant', $source->getFeedType());
        self::assertSame(1, $source->getPosition());
    }

    /**
     * @test
     */
    public function it_manages_fields_and_sets_the_back_reference(): void
    {
        $source = new FeedSource();
        $field = new FeedField();

        $source->addField($field);

        self::assertTrue($source->hasField($field));
        self::assertSame($source, $field->getSource());
        self::assertCount(1, $source->getFields());

        // adding the same field again is idempotent
        $source->addField($field);
        self::assertCount(1, $source->getFields());

        $source->removeField($field);

        self::assertFalse($source->hasField($field));
        self::assertNull($field->getSource());
    }

    /**
     * @test
     */
    public function it_manages_filters_and_sets_the_back_reference(): void
    {
        $source = new FeedSource();
        $filter = new FeedFilter();

        $source->addFilter($filter);

        self::assertTrue($source->hasFilter($filter));
        self::assertSame($source, $filter->getSource());

        $source->removeFilter($filter);

        self::assertFalse($source->hasFilter($filter));
        self::assertNull($filter->getSource());
    }
}
