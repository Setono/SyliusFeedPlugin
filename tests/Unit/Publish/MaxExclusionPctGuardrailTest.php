<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Publish;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Model\FeedContextResult;
use Setono\SyliusFeedPlugin\Publish\MaxExclusionPctGuardrail;

final class MaxExclusionPctGuardrailTest extends TestCase
{
    /**
     * @test
     */
    public function it_exposes_its_type(): void
    {
        self::assertSame('max_exclusion_pct', (new MaxExclusionPctGuardrail())->getType());
    }

    /**
     * @test
     */
    public function it_trips_on_the_absolute_threshold_without_a_baseline(): void
    {
        // 60 excluded of 100 processed = 60% exclusion, beyond the 50% threshold
        self::assertTrue((new MaxExclusionPctGuardrail())->evaluate($this->result(40, 60), null, ['pct' => 50]));
    }

    /**
     * @test
     */
    public function it_does_not_trip_when_the_ratio_is_within_the_threshold(): void
    {
        // 40 excluded of 100 = 40% exclusion, within the 50% threshold
        self::assertFalse((new MaxExclusionPctGuardrail())->evaluate($this->result(60, 40), null, ['pct' => 50]));
    }

    /**
     * @test
     */
    public function it_does_not_trip_when_the_ratio_did_not_jump_beyond_the_baseline(): void
    {
        // candidate 60% and baseline already 70% => a stable/lower exclusion share, not a regression
        self::assertFalse(
            (new MaxExclusionPctGuardrail())->evaluate($this->result(40, 60), $this->result(30, 70), ['pct' => 50]),
        );
    }

    /**
     * @test
     */
    public function it_trips_when_the_ratio_exceeds_both_the_threshold_and_the_baseline(): void
    {
        // candidate 60% exclusion vs a baseline 20% exclusion => a real jump past the 50% threshold
        self::assertTrue(
            (new MaxExclusionPctGuardrail())->evaluate($this->result(40, 60), $this->result(80, 20), ['pct' => 50]),
        );
    }

    /**
     * @test
     */
    public function it_does_not_trip_when_nothing_was_processed(): void
    {
        self::assertFalse((new MaxExclusionPctGuardrail())->evaluate($this->result(0, 0), null, ['pct' => 50]));
    }

    private function result(int $itemCount, int $excludedCount): FeedContextResult
    {
        $result = new FeedContextResult();
        $result->setItemCount($itemCount);
        $result->setExcludedCount($excludedCount);

        return $result;
    }
}
