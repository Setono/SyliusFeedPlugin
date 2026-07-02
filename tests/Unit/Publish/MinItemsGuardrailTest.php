<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Publish;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Model\FeedContextResult;
use Setono\SyliusFeedPlugin\Publish\MinItemsGuardrail;

final class MinItemsGuardrailTest extends TestCase
{
    /**
     * @test
     */
    public function it_exposes_its_type(): void
    {
        self::assertSame('min_items', (new MinItemsGuardrail())->getType());
    }

    /**
     * @test
     */
    public function it_trips_when_the_item_count_is_below_the_minimum(): void
    {
        self::assertTrue((new MinItemsGuardrail())->evaluate($this->result(9), null, ['min' => 10]));
    }

    /**
     * @test
     */
    public function it_does_not_trip_when_the_item_count_meets_the_minimum(): void
    {
        $guardrail = new MinItemsGuardrail();

        self::assertFalse($guardrail->evaluate($this->result(10), null, ['min' => 10]));
        self::assertFalse($guardrail->evaluate($this->result(11), null, ['min' => 10]));
    }

    /**
     * @test
     */
    public function it_ignores_the_baseline(): void
    {
        self::assertTrue((new MinItemsGuardrail())->evaluate($this->result(1), $this->result(1000), ['min' => 10]));
    }

    private function result(int $itemCount): FeedContextResult
    {
        $result = new FeedContextResult();
        $result->setItemCount($itemCount);

        return $result;
    }
}
