<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Publish;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Model\FeedContextResult;
use Setono\SyliusFeedPlugin\Publish\MaxDropPctGuardrail;

final class MaxDropPctGuardrailTest extends TestCase
{
    /**
     * @test
     */
    public function it_exposes_its_type(): void
    {
        self::assertSame('max_drop_pct', (new MaxDropPctGuardrail())->getType());
    }

    /**
     * @test
     */
    public function it_does_not_trip_without_a_baseline(): void
    {
        self::assertFalse((new MaxDropPctGuardrail())->evaluate($this->result(0), null, ['pct' => 40]));
    }

    /**
     * @test
     */
    public function it_trips_when_the_drop_exceeds_the_threshold(): void
    {
        // 50_000 -> 5_000 is a 90% drop, well beyond the 40% threshold
        self::assertTrue((new MaxDropPctGuardrail())->evaluate($this->result(5000), $this->result(50000), ['pct' => 40]));
    }

    /**
     * @test
     */
    public function it_does_not_trip_when_the_drop_is_within_the_threshold(): void
    {
        // 100 -> 70 is a 30% drop, within a 40% threshold
        self::assertFalse((new MaxDropPctGuardrail())->evaluate($this->result(70), $this->result(100), ['pct' => 40]));
    }

    /**
     * @test
     */
    public function it_does_not_trip_when_the_candidate_grew_or_held_steady(): void
    {
        $guardrail = new MaxDropPctGuardrail();

        self::assertFalse($guardrail->evaluate($this->result(120), $this->result(100), ['pct' => 40]));
        self::assertFalse($guardrail->evaluate($this->result(100), $this->result(100), ['pct' => 40]));
    }

    /**
     * @test
     */
    public function it_does_not_trip_when_the_baseline_was_empty(): void
    {
        self::assertFalse((new MaxDropPctGuardrail())->evaluate($this->result(0), $this->result(0), ['pct' => 40]));
    }

    private function result(int $itemCount): FeedContextResult
    {
        $result = new FeedContextResult();
        $result->setItemCount($itemCount);

        return $result;
    }
}
