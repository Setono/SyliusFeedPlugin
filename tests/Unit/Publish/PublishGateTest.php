<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Publish;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Model\FeedContextResult;
use Setono\SyliusFeedPlugin\Publish\GuardrailRegistry;
use Setono\SyliusFeedPlugin\Publish\MaxDropPctGuardrail;
use Setono\SyliusFeedPlugin\Publish\MaxExclusionPctGuardrail;
use Setono\SyliusFeedPlugin\Publish\MaxGrowthPctGuardrail;
use Setono\SyliusFeedPlugin\Publish\MinBytesGuardrail;
use Setono\SyliusFeedPlugin\Publish\MinItemsGuardrail;
use Setono\SyliusFeedPlugin\Publish\NonEmptyGuardrail;
use Setono\SyliusFeedPlugin\Publish\PublishGate;

final class PublishGateTest extends TestCase
{
    /**
     * @test
     */
    public function it_does_not_block_when_no_guardrails_are_configured(): void
    {
        $decision = $this->gate()->evaluate($this->result(0), null, []);

        self::assertFalse($decision->blocked);
        self::assertSame([], $decision->reasons);
    }

    /**
     * @test
     */
    public function it_blocks_when_a_block_severity_guardrail_trips(): void
    {
        $decision = $this->gate()->evaluate(
            $this->result(0),
            null,
            [['type' => 'non_empty', 'severity' => 'block']],
        );

        self::assertTrue($decision->blocked);
        self::assertCount(1, $decision->reasons);
        self::assertStringContainsString('non_empty', $decision->reasons[0]);
    }

    /**
     * @test
     */
    public function it_records_a_reason_but_does_not_block_for_a_warn_guardrail(): void
    {
        $decision = $this->gate()->evaluate(
            $this->result(0),
            null,
            [['type' => 'non_empty', 'severity' => 'warn']],
        );

        self::assertFalse($decision->blocked);
        self::assertCount(1, $decision->reasons);
        self::assertStringContainsString('warn', $decision->reasons[0]);
    }

    /**
     * @test
     */
    public function it_skips_unknown_guardrail_types(): void
    {
        $decision = $this->gate()->evaluate(
            $this->result(0),
            null,
            [['type' => 'does_not_exist', 'severity' => 'block']],
        );

        self::assertFalse($decision->blocked);
        self::assertSame([], $decision->reasons);
    }

    /**
     * @test
     */
    public function it_does_not_record_a_reason_for_a_guardrail_that_does_not_trip(): void
    {
        $decision = $this->gate()->evaluate(
            $this->result(100),
            null,
            [['type' => 'non_empty', 'severity' => 'block']],
        );

        self::assertFalse($decision->blocked);
        self::assertSame([], $decision->reasons);
    }

    /**
     * @test
     */
    public function it_blocks_when_any_block_guardrail_trips_among_several(): void
    {
        $decision = $this->gate()->evaluate(
            $this->result(0),
            null,
            [
                ['type' => 'min_bytes', 'params' => ['bytes' => 10], 'severity' => 'warn'],
                ['type' => 'non_empty', 'severity' => 'block'],
            ],
        );

        self::assertTrue($decision->blocked);
        self::assertCount(2, $decision->reasons);
    }

    /**
     * @test
     */
    public function it_defaults_missing_severity_to_block(): void
    {
        $decision = $this->gate()->evaluate(
            $this->result(0),
            null,
            [['type' => 'non_empty']],
        );

        self::assertTrue($decision->blocked);
    }

    /**
     * @test
     */
    public function it_blocks_the_50k_to_5k_collapse(): void
    {
        // Acceptance (§6.6): a candidate of 5000 items vs a baseline of 50000, with a
        // max_drop_pct guardrail set to 40% and block severity, must block promotion.
        $decision = $this->gate()->evaluate(
            $this->result(5000),
            $this->result(50000),
            [['type' => 'max_drop_pct', 'params' => ['pct' => 40], 'severity' => 'block']],
        );

        self::assertTrue($decision->blocked);
        self::assertCount(1, $decision->reasons);
        self::assertStringContainsString('max_drop_pct', $decision->reasons[0]);
    }

    private function gate(): PublishGate
    {
        return new PublishGate(new GuardrailRegistry([
            new MinItemsGuardrail(),
            new MaxDropPctGuardrail(),
            new NonEmptyGuardrail(),
            new MinBytesGuardrail(),
            new MaxExclusionPctGuardrail(),
            new MaxGrowthPctGuardrail(),
        ]));
    }

    private function result(int $itemCount): FeedContextResult
    {
        $result = new FeedContextResult();
        $result->setItemCount($itemCount);

        return $result;
    }
}
