<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\IsNotEmpty;

final class IsNotEmptyTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('not_empty', (new IsNotEmpty())->getName());
    }

    /**
     * @test
     */
    public function it_matches_falsy_but_non_empty_values(): void
    {
        $operator = new IsNotEmpty();

        self::assertTrue($operator->matches(0, []));
        self::assertTrue($operator->matches('0', []));
        self::assertTrue($operator->matches(false, []));
    }

    /**
     * @test
     */
    public function it_does_not_match_null_empty_string_or_empty_array(): void
    {
        $operator = new IsNotEmpty();

        self::assertFalse($operator->matches(null, []));
        self::assertFalse($operator->matches('', []));
        self::assertFalse($operator->matches([], []));
    }
}
