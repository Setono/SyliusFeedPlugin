<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\Equals;

final class EqualsTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('equals', (new Equals())->getName());
    }

    /**
     * @test
     */
    public function it_matches_when_scalars_are_equal_as_strings(): void
    {
        $operator = new Equals();

        self::assertTrue($operator->matches(42, ['value' => '42']));
        self::assertTrue($operator->matches('foo', ['value' => 'foo']));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_values_differ(): void
    {
        $operator = new Equals();

        self::assertFalse($operator->matches('foo', ['value' => 'bar']));
    }

    /**
     * @test
     */
    public function it_uses_strict_comparison_for_non_scalars(): void
    {
        $operator = new Equals();

        $object = new \stdClass();

        self::assertFalse($operator->matches($object, ['value' => new \stdClass()]));
        self::assertTrue($operator->matches($object, ['value' => $object]));
        self::assertTrue($operator->matches(null, ['value' => null]));
    }
}
