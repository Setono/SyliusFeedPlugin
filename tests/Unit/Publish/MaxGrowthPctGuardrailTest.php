<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Publish;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Model\FeedContextResult;
use Setono\SyliusFeedPlugin\Publish\MaxGrowthPctGuardrail;

final class MaxGrowthPctGuardrailTest extends TestCase
{
    /**
     * @test
     */
    public function it_exposes_its_type(): void
    {
        self::assertSame('max_growth_pct', (new MaxGrowthPctGuardrail())->getType());
    }

    /**
     * @test
     */
    public function it_does_not_trip_without_a_baseline(): void
    {
        self::assertFalse((new MaxGrowthPctGuardrail())->evaluate($this->result(1_000_000), null, ['pct' => 50]));
    }

    /**
     * @test
     */
    public function it_trips_when_the_growth_exceeds_the_threshold(): void
    {
        // 100 -> 250 is 150% growth, beyond the 50% threshold
        self::assertTrue((new MaxGrowthPctGuardrail())->evaluate($this->result(250), $this->result(100), ['pct' => 50]));
    }

    /**
     * @test
     */
    public function it_does_not_trip_when_the_growth_is_within_the_threshold(): void
    {
        // 100 -> 140 is 40% growth, within a 50% threshold
        self::assertFalse((new MaxGrowthPctGuardrail())->evaluate($this->result(140), $this->result(100), ['pct' => 50]));
    }

    /**
     * @test
     */
    public function it_does_not_trip_when_the_candidate_shrank_or_held_steady(): void
    {
        $guardrail = new MaxGrowthPctGuardrail();

        self::assertFalse($guardrail->evaluate($this->result(80), $this->result(100), ['pct' => 50]));
        self::assertFalse($guardrail->evaluate($this->result(100), $this->result(100), ['pct' => 50]));
    }

    /**
     * @test
     */
    public function it_does_not_trip_when_the_baseline_was_empty(): void
    {
        self::assertFalse((new MaxGrowthPctGuardrail())->evaluate($this->result(500), $this->result(0), ['pct' => 50]));
    }

    private function result(int $itemCount): FeedContextResult
    {
        $result = new FeedContextResult();
        $result->setItemCount($itemCount);

        return $result;
    }
}
