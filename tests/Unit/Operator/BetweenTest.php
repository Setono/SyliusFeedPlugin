<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\Between;

final class BetweenTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('between', (new Between())->getName());
    }

    /**
     * @test
     */
    public function it_matches_when_value_is_within_the_inclusive_range_given_as_a_list(): void
    {
        $operator = new Between();

        self::assertTrue($operator->matches(5, ['value' => [1, 10]]));
        self::assertTrue($operator->matches(1, ['value' => [1, 10]]));
        self::assertTrue($operator->matches(10, ['value' => [1, 10]]));
    }

    /**
     * @test
     */
    public function it_matches_when_the_operand_is_a_min_max_map(): void
    {
        self::assertTrue((new Between())->matches(5, ['value' => ['min' => 1, 'max' => 10]]));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_value_is_outside_the_range(): void
    {
        $operator = new Between();

        self::assertFalse($operator->matches(11, ['value' => [1, 10]]));
        self::assertFalse($operator->matches(0, ['value' => [1, 10]]));
    }

    /**
     * @test
     */
    public function it_returns_false_when_value_is_not_numeric(): void
    {
        self::assertFalse((new Between())->matches('abc', ['value' => [1, 10]]));
    }

    /**
     * @test
     */
    public function it_returns_false_when_the_operand_shape_is_invalid(): void
    {
        $operator = new Between();

        self::assertFalse($operator->matches(5, ['value' => [1, 2, 3]]));
        self::assertFalse($operator->matches(5, ['value' => 'not-a-list']));
    }
}
