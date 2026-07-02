<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\LessThan;

final class LessThanTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('lt', (new LessThan())->getName());
    }

    /**
     * @test
     */
    public function it_matches_when_value_is_less_than_the_operand(): void
    {
        self::assertTrue((new LessThan())->matches(3, ['value' => 5]));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_value_is_not_less_than_the_operand(): void
    {
        $operator = new LessThan();

        self::assertFalse($operator->matches(5, ['value' => 3]));
        self::assertFalse($operator->matches(3, ['value' => 3]));
    }

    /**
     * @test
     */
    public function it_returns_false_when_either_side_is_not_numeric(): void
    {
        self::assertFalse((new LessThan())->matches('abc', ['value' => 3]));
    }
}
