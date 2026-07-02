<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\NotEquals;

final class NotEqualsTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('not_equals', (new NotEquals())->getName());
    }

    /**
     * @test
     */
    public function it_matches_when_values_differ(): void
    {
        $operator = new NotEquals();

        self::assertTrue($operator->matches('foo', ['value' => 'bar']));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_scalars_are_equal_as_strings(): void
    {
        $operator = new NotEquals();

        self::assertFalse($operator->matches(42, ['value' => '42']));
    }

    /**
     * @test
     */
    public function it_uses_strict_comparison_for_non_scalars(): void
    {
        $operator = new NotEquals();

        self::assertTrue($operator->matches(new \stdClass(), ['value' => new \stdClass()]));
    }
}
