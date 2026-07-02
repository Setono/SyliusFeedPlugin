<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\EndsWith;

final class EndsWithTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('ends_with', (new EndsWith())->getName());
    }

    /**
     * @test
     */
    public function it_matches_when_value_ends_with_the_operand(): void
    {
        self::assertTrue((new EndsWith())->matches('SKU-123', ['value' => '123']));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_value_does_not_end_with_the_operand(): void
    {
        self::assertFalse((new EndsWith())->matches('SKU-123', ['value' => 'ABC']));
    }

    /**
     * @test
     */
    public function it_returns_false_when_value_is_not_a_string(): void
    {
        self::assertFalse((new EndsWith())->matches(123, ['value' => '3']));
    }
}
