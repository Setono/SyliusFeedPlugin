<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\Contains;

final class ContainsTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('contains', (new Contains())->getName());
    }

    /**
     * @test
     */
    public function it_matches_when_array_value_contains_the_operand(): void
    {
        self::assertTrue((new Contains())->matches(['a', 'b', 'c'], ['value' => 'b']));
    }

    /**
     * @test
     */
    public function it_matches_when_string_value_contains_the_operand_substring(): void
    {
        self::assertTrue((new Contains())->matches('hello world', ['value' => 'wor']));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_array_does_not_contain_the_operand(): void
    {
        self::assertFalse((new Contains())->matches(['a', 'b', 'c'], ['value' => 'd']));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_string_does_not_contain_the_operand(): void
    {
        self::assertFalse((new Contains())->matches('hello world', ['value' => 'xyz']));
    }

    /**
     * @test
     */
    public function it_returns_false_for_a_value_that_is_neither_array_nor_string(): void
    {
        self::assertFalse((new Contains())->matches(42, ['value' => '4']));
    }
}
