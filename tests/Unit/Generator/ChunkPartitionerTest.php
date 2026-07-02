<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\DataSource\DataSourceInterface;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Generator\ChunkPartitioner;
use Setono\SyliusFeedPlugin\Generator\ChunkRange;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;

/**
 * Unit tests for {@see ChunkPartitioner}: the ranges cover every id, are ordered and non-overlapping,
 * and the partitioner declines to fan out (returns `[]`) when the source is small, multi-source, or
 * cannot be partitioned by id.
 */
final class ChunkPartitionerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_partitions_a_source_into_ordered_ranges_covering_all_ids(): void
    {
        $partitioner = new ChunkPartitioner($this->registry(count: 5, idRange: new ChunkRange(1, 5)));

        $ranges = $partitioner->partition($this->feed(), new FeedContext(), 2);

        // ceil(5 / 2) = 3 chunks over span [1..5], width ceil(5 / 3) = 2.
        self::assertEquals([
            new ChunkRange(1, 2),
            new ChunkRange(3, 4),
            new ChunkRange(5, 5),
        ], $ranges);

        // Ordered, non-overlapping and covering every id from min to max.
        $covered = [];
        foreach ($ranges as $range) {
            self::assertGreaterThanOrEqual($range->start, $range->end);
            for ($id = $range->start; $id <= $range->end; ++$id) {
                $covered[] = $id;
            }
        }
        self::assertSame([1, 2, 3, 4, 5], $covered);
    }

    /**
     * @test
     */
    public function it_covers_a_sparse_id_range_without_exploding_the_chunk_count(): void
    {
        $partitioner = new ChunkPartitioner($this->registry(count: 2, idRange: new ChunkRange(1, 100)));

        $ranges = $partitioner->partition($this->feed(), new FeedContext(), 1);

        // ceil(2 / 1) = 2 chunks over span [1..100], width ceil(100 / 2) = 50 — bounded despite the gap.
        self::assertEquals([
            new ChunkRange(1, 50),
            new ChunkRange(51, 100),
        ], $ranges);
    }

    /**
     * @test
     */
    public function it_stays_inline_when_the_source_fits_in_one_chunk(): void
    {
        $partitioner = new ChunkPartitioner($this->registry(count: 3, idRange: new ChunkRange(1, 3)));

        self::assertSame([], $partitioner->partition($this->feed(), new FeedContext(), 10));
    }

    /**
     * @test
     */
    public function it_stays_inline_when_the_source_cannot_be_partitioned_by_id(): void
    {
        $partitioner = new ChunkPartitioner($this->registry(count: 100, idRange: null));

        self::assertSame([], $partitioner->partition($this->feed(), new FeedContext(), 1));
    }

    /**
     * @test
     */
    public function it_stays_inline_for_a_multi_source_feed(): void
    {
        $registry = $this->prophesize(FeedTypeRegistryInterface::class);
        $partitioner = new ChunkPartitioner($registry->reveal());

        $feed = $this->prophesize(FeedInterface::class);
        $feed->getSources()->willReturn(new ArrayCollection([
            $this->source()->reveal(),
            $this->source()->reveal(),
        ]));

        self::assertSame([], $partitioner->partition($feed->reveal(), new FeedContext(), 1));
    }

    private function registry(int $count, ?ChunkRange $idRange): FeedTypeRegistryInterface
    {
        $dataSource = $this->prophesize(DataSourceInterface::class);
        $dataSource->count(Argument::cetera())->willReturn($count);
        $dataSource->getIdRange(Argument::cetera())->willReturn($idRange);

        $feedType = $this->prophesize(FeedTypeInterface::class);
        $feedType->getDataSource()->willReturn($dataSource->reveal());

        $registry = $this->prophesize(FeedTypeRegistryInterface::class);
        $registry->get('product_variant')->willReturn($feedType->reveal());

        return $registry->reveal();
    }

    private function feed(): FeedInterface
    {
        $feed = $this->prophesize(FeedInterface::class);
        $feed->getSources()->willReturn(new ArrayCollection([$this->source()->reveal()]));

        return $feed->reveal();
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy<FeedSourceInterface>
     */
    private function source(): object
    {
        $source = $this->prophesize(FeedSourceInterface::class);
        $source->getFeedType()->willReturn('product_variant');
        $source->getFilters()->willReturn(new ArrayCollection());

        return $source;
    }
}
