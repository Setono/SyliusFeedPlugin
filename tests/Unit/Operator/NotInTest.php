<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Operator;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Operator\NotIn;

final class NotInTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_the_operator_name(): void
    {
        self::assertSame('not_in', (new NotIn())->getName());
    }

    /**
     * @test
     */
    public function it_matches_when_value_is_not_in_the_list(): void
    {
        $operator = new NotIn();

        self::assertTrue($operator->matches('d', ['value' => ['a', 'b', 'c']]));
    }

    /**
     * @test
     */
    public function it_does_not_match_when_value_is_in_the_list(): void
    {
        $operator = new NotIn();

        self::assertFalse($operator->matches('b', ['value' => ['a', 'b', 'c']]));
    }

    /**
     * @test
     */
    public function it_treats_a_non_array_operand_as_a_single_element_list(): void
    {
        $operator = new NotIn();

        self::assertFalse($operator->matches('a', ['value' => 'a']));
        self::assertTrue($operator->matches('a', ['value' => 'b']));
    }
}
