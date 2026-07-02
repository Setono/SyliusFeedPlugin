<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\IsEmpty;

final class IsEmptyTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('empty', (new IsEmpty())->getName());
    }

    /**
     * @test
     */
    public function it_matches_null_empty_string_and_empty_array(): void
    {
        $operator = new IsEmpty();

        self::assertTrue($operator->matches(null, []));
        self::assertTrue($operator->matches('', []));
        self::assertTrue($operator->matches([], []));
    }

    /**
     * @test
     */
    public function it_does_not_match_falsy_but_non_empty_values(): void
    {
        $operator = new IsEmpty();

        self::assertFalse($operator->matches(0, []));
        self::assertFalse($operator->matches('0', []));
        self::assertFalse($operator->matches(false, []));
    }
}
