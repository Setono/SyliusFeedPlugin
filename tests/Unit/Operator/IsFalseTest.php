<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\IsFalse;

final class IsFalseTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('false', (new IsFalse())->getName());
    }

    /**
     * @test
     */
    public function it_matches_falsy_values(): void
    {
        $operator = new IsFalse();

        self::assertTrue($operator->matches(false, []));
        self::assertTrue($operator->matches(0, []));
        self::assertTrue($operator->matches('', []));
        self::assertTrue($operator->matches(null, []));
    }

    /**
     * @test
     */
    public function it_does_not_match_truthy_values(): void
    {
        $operator = new IsFalse();

        self::assertFalse($operator->matches(true, []));
        self::assertFalse($operator->matches(1, []));
        self::assertFalse($operator->matches('yes', []));
    }
}
