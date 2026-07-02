<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\IsTrue;

final class IsTrueTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('true', (new IsTrue())->getName());
    }

    /**
     * @test
     */
    public function it_matches_truthy_values(): void
    {
        $operator = new IsTrue();

        self::assertTrue($operator->matches(true, []));
        self::assertTrue($operator->matches(1, []));
        self::assertTrue($operator->matches('yes', []));
    }

    /**
     * @test
     */
    public function it_does_not_match_falsy_values(): void
    {
        $operator = new IsTrue();

        self::assertFalse($operator->matches(false, []));
        self::assertFalse($operator->matches(0, []));
        self::assertFalse($operator->matches('', []));
        self::assertFalse($operator->matches(null, []));
    }
}
