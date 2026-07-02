<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\Matches;

final class MatchesTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('matches', (new Matches())->getName());
    }

    /**
     * @test
     */
    public function it_matches_when_the_value_matches_the_pattern(): void
    {
        self::assertTrue((new Matches())->matches('SKU-123', ['value' => '/^SKU/i']));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_the_value_does_not_match_the_pattern(): void
    {
        self::assertFalse((new Matches())->matches('ABC-123', ['value' => '/^SKU/i']));
    }

    /**
     * @test
     */
    public function it_returns_false_when_the_value_is_not_scalar(): void
    {
        self::assertFalse((new Matches())->matches(['SKU-123'], ['value' => '/^SKU/i']));
    }

    /**
     * @test
     */
    public function it_returns_false_when_the_operand_is_not_a_non_empty_string(): void
    {
        $operator = new Matches();

        self::assertFalse($operator->matches('SKU-123', ['value' => '']));
        self::assertFalse($operator->matches('SKU-123', ['value' => null]));
    }

    /**
     * @test
     */
    public function it_returns_false_when_the_pattern_is_invalid(): void
    {
        self::assertFalse((new Matches())->matches('SKU-123', ['value' => '/[/']));
    }
}
