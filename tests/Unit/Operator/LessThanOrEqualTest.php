<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\LessThanOrEqual;

final class LessThanOrEqualTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('lte', (new LessThanOrEqual())->getName());
    }

    /**
     * @test
     */
    public function it_matches_when_value_is_less_than_or_equal_to_the_operand(): void
    {
        $operator = new LessThanOrEqual();

        self::assertTrue($operator->matches(3, ['value' => 5]));
        self::assertTrue($operator->matches(3, ['value' => 3]));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_value_is_greater_than_the_operand(): void
    {
        self::assertFalse((new LessThanOrEqual())->matches(5, ['value' => 3]));
    }

    /**
     * @test
     */
    public function it_returns_false_when_either_side_is_not_numeric(): void
    {
        self::assertFalse((new LessThanOrEqual())->matches('abc', ['value' => 3]));
    }
}
