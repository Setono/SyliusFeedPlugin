<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Publish;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Model\FeedContextResult;
use Setono\SyliusFeedPlugin\Publish\NonEmptyGuardrail;

final class NonEmptyGuardrailTest extends TestCase
{
    /**
     * @test
     */
    public function it_exposes_its_type(): void
    {
        self::assertSame('non_empty', (new NonEmptyGuardrail())->getType());
    }

    /**
     * @test
     */
    public function it_trips_when_the_candidate_is_empty(): void
    {
        self::assertTrue((new NonEmptyGuardrail())->evaluate($this->result(0), null, []));
    }

    /**
     * @test
     */
    public function it_does_not_trip_when_the_candidate_has_items(): void
    {
        $guardrail = new NonEmptyGuardrail();

        self::assertFalse($guardrail->evaluate($this->result(1), null, []));
        self::assertFalse($guardrail->evaluate($this->result(1), $this->result(0), []));
    }

    private function result(int $itemCount): FeedContextResult
    {
        $result = new FeedContextResult();
        $result->setItemCount($itemCount);

        return $result;
    }
}
