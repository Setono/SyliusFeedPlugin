<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Filter;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Filter\FilterSet;
use Setono\SyliusFeedPlugin\Model\FeedFilterInterface;

/**
 * @covers \Setono\SyliusFeedPlugin\Filter\FilterSet
 */
final class FilterSetTest extends TestCase
{
    use ProphecyTrait;

    private function filter(string $stage): FeedFilterInterface
    {
        $filter = $this->prophesize(FeedFilterInterface::class);
        $filter->getStage()->willReturn($stage);

        return $filter->reveal();
    }

    /**
     * @test
     */
    public function it_is_empty_by_default(): void
    {
        $set = new FilterSet();

        self::assertTrue($set->isEmpty());
        self::assertSame([], $set->getPreFilters());
        self::assertSame([], $set->getPostFilters());
    }

    /**
     * @test
     */
    public function it_partitions_filters_into_pre_and_post_stages(): void
    {
        $pre = $this->filter(FeedFilterInterface::STAGE_PRE);
        $post = $this->filter(FeedFilterInterface::STAGE_POST);

        $set = new FilterSet([$pre, $post]);

        self::assertFalse($set->isEmpty());
        self::assertSame([$pre], $set->getPreFilters());
        self::assertSame([$post], $set->getPostFilters());
    }
}
