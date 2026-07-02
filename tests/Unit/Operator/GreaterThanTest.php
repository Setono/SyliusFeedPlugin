<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\GreaterThan;

final class GreaterThanTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('gt', (new GreaterThan())->getName());
    }

    /**
     * @test
     */
    public function it_matches_when_value_is_greater_than_the_operand(): void
    {
        self::assertTrue((new GreaterThan())->matches(5, ['value' => 3]));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_value_is_not_greater_than_the_operand(): void
    {
        self::assertFalse((new GreaterThan())->matches(3, ['value' => 5]));
        self::assertFalse((new GreaterThan())->matches(3, ['value' => 3]));
    }

    /**
     * @test
     */
    public function it_returns_false_when_either_side_is_not_numeric(): void
    {
        $operator = new GreaterThan();

        self::assertFalse($operator->matches('abc', ['value' => 3]));
        self::assertFalse($operator->matches(5, ['value' => 'abc']));
    }
}
