<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\In;

final class InTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('in', (new In())->getName());
    }

    /**
     * @test
     */
    public function it_matches_when_value_is_in_the_list(): void
    {
        $operator = new In();

        self::assertTrue($operator->matches('b', ['value' => ['a', 'b', 'c']]));
        self::assertTrue($operator->matches(2, ['value' => ['1', '2', '3']]));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_value_is_not_in_the_list(): void
    {
        $operator = new In();

        self::assertFalse($operator->matches('d', ['value' => ['a', 'b', 'c']]));
    }

    /**
     * @test
     */
    public function it_treats_a_non_array_operand_as_a_single_element_list(): void
    {
        $operator = new In();

        self::assertTrue($operator->matches('a', ['value' => 'a']));
        self::assertFalse($operator->matches('a', ['value' => 'b']));
    }
}
