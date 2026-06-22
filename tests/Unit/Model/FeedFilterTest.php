<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Model\FeedFilter;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;

final class FeedFilterTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_defaults_to_an_including_pre_stage_filter(): void
    {
        $filter = new FeedFilter();

        self::assertNull($filter->getId());
        self::assertSame(FeedFilter::ACTION_INCLUDE, $filter->getAction());
        self::assertSame(FeedFilter::STAGE_PRE, $filter->getStage());
    }

    /**
     * @test
     */
    public function it_holds_the_field_operator_value_action_and_stage(): void
    {
        $source = $this->prophesize(FeedSourceInterface::class)->reveal();

        $filter = new FeedFilter();
        $filter->setSource($source);
        $filter->setField('availability');
        $filter->setOperator('equals');
        $filter->setValue(['in_stock']);
        $filter->setAction(FeedFilter::ACTION_EXCLUDE);
        $filter->setStage(FeedFilter::STAGE_POST);

        self::assertSame($source, $filter->getSource());
        self::assertSame('availability', $filter->getField());
        self::assertSame('equals', $filter->getOperator());
        self::assertSame(['in_stock'], $filter->getValue());
        self::assertSame(FeedFilter::ACTION_EXCLUDE, $filter->getAction());
        self::assertSame(FeedFilter::STAGE_POST, $filter->getStage());
    }
}
