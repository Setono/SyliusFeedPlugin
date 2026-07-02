<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\StartsWith;

final class StartsWithTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('starts_with', (new StartsWith())->getName());
    }

    /**
     * @test
     */
    public function it_matches_when_value_starts_with_the_operand(): void
    {
        self::assertTrue((new StartsWith())->matches('SKU-123', ['value' => 'SKU-']));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_value_does_not_start_with_the_operand(): void
    {
        self::assertFalse((new StartsWith())->matches('SKU-123', ['value' => 'ABC']));
    }

    /**
     * @test
     */
    public function it_returns_false_when_value_is_not_a_string(): void
    {
        self::assertFalse((new StartsWith())->matches(123, ['value' => '1']));
    }
}
