<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\GreaterThanOrEqual;

final class GreaterThanOrEqualTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('gte', (new GreaterThanOrEqual())->getName());
    }

    /**
     * @test
     */
    public function it_matches_when_value_is_greater_than_or_equal_to_the_operand(): void
    {
        $operator = new GreaterThanOrEqual();

        self::assertTrue($operator->matches(5, ['value' => 3]));
        self::assertTrue($operator->matches(3, ['value' => 3]));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_value_is_less_than_the_operand(): void
    {
        self::assertFalse((new GreaterThanOrEqual())->matches(2, ['value' => 3]));
    }

    /**
     * @test
     */
    public function it_returns_false_when_either_side_is_not_numeric(): void
    {
        self::assertFalse((new GreaterThanOrEqual())->matches('abc', ['value' => 3]));
    }
}
