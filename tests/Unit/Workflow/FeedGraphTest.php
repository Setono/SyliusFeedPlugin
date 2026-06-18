<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Workflow;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;

/**
 * @covers \Setono\SyliusFeedPlugin\Workflow\FeedGraph
 */
final class FeedGraphTest extends TestCase
{
    /**
     * @test
     */
    public function it_lists_all_places(): void
    {
        self::assertSame(
            [
                FeedGraph::STATE_READY,
                FeedGraph::STATE_PROCESSING,
                FeedGraph::STATE_COMPLETED,
                FeedGraph::STATE_FAILED,
            ],
            FeedGraph::getStates(),
        );
    }
}
