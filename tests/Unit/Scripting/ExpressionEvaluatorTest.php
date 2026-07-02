<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Scripting;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Lookup\InMemoryLookup;
use Setono\SyliusFeedPlugin\Lookup\LookupInterface;
use Setono\SyliusFeedPlugin\Scripting\ExpressionEvaluator;

final class ExpressionEvaluatorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_evaluates_arithmetic(): void
    {
        $evaluator = new ExpressionEvaluator(new InMemoryLookup());

        self::assertSame(3, $evaluator->evaluate('1 + 2', []));
    }

    /**
     * @test
     */
    public function it_evaluates_string_concatenation_using_variables(): void
    {
        $evaluator = new ExpressionEvaluator(new InMemoryLookup());

        self::assertSame('hi!', $evaluator->evaluate('value ~ "!"', ['value' => 'hi']));
    }

    /**
     * @test
     */
    public function it_evaluates_array_access_on_the_fields_variable(): void
    {
        $evaluator = new ExpressionEvaluator(new InMemoryLookup());

        self::assertSame(10, $evaluator->evaluate("fields['price']", ['fields' => ['price' => 10]]));
    }

    /**
     * @test
     */
    public function it_delegates_the_lookup_function_to_the_lookup_service(): void
    {
        $lookup = new InMemoryLookup();
        $lookup->addTable('t', ['k' => ['c' => 'B']]);

        $evaluator = new ExpressionEvaluator($lookup);

        self::assertSame('B', $evaluator->evaluate("lookup('t', 'k', 'c')", []));
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_lookup_function_misses(): void
    {
        $evaluator = new ExpressionEvaluator(new InMemoryLookup());

        self::assertNull($evaluator->evaluate("lookup('t', 'k', 'c')", []));
    }

    /**
     * @test
     */
    public function it_calls_the_injected_lookup_with_the_string_arguments(): void
    {
        $lookup = $this->prophesize(LookupInterface::class);
        $lookup->get('badges', 'SKU-1', 'suffix')->willReturn('Bestseller');

        $evaluator = new ExpressionEvaluator($lookup->reveal());

        self::assertSame('Bestseller', $evaluator->evaluate("lookup('badges', 'SKU-1', 'suffix')", []));
    }
}
