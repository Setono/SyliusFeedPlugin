<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Scripting\ExpressionEvaluatorInterface;
use Setono\SyliusFeedPlugin\Transformation\Expression;

final class ExpressionTest extends TestCase
{
    use ProphecyTrait;

    private FeedItem $item;

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
    }

    /**
     * @test
     */
    public function it_has_the_expression_type(): void
    {
        $evaluator = $this->prophesize(ExpressionEvaluatorInterface::class)->reveal();
        $transformation = new Expression($evaluator);

        self::assertSame('expression', $transformation->getType());

        $config = Expression::of('value ~ "!"');

        self::assertSame('expression', $config->getType());
        self::assertSame(['expression' => 'value ~ "!"'], $config->getParams());
    }

    /**
     * @test
     */
    public function it_evaluates_the_expression_via_the_evaluator(): void
    {
        $evaluator = $this->prophesize(ExpressionEvaluatorInterface::class);
        $evaluator->evaluate(Argument::type('string'), Argument::type('array'))->willReturn('hi!');

        $transformation = new Expression($evaluator->reveal());

        $result = $transformation->apply('hi', ['expression' => 'value ~ "!"'], $this->item);

        self::assertSame('hi!', $result);
    }

    /**
     * @test
     */
    public function it_passes_the_expression_and_scripting_variables_to_the_evaluator(): void
    {
        $evaluator = $this->prophesize(ExpressionEvaluatorInterface::class);
        $evaluator->evaluate('value ~ "!"', Argument::that(
            static fn (array $variables): bool => 'hi' === $variables['value'],
        ))->willReturn('hi!');

        $transformation = new Expression($evaluator->reveal());

        self::assertSame('hi!', $transformation->apply('hi', ['expression' => 'value ~ "!"'], $this->item));
    }

    /**
     * @test
     */
    public function it_is_a_no_op_when_the_expression_is_missing(): void
    {
        $evaluator = $this->prophesize(ExpressionEvaluatorInterface::class);
        $evaluator->evaluate(Argument::cetera())->shouldNotBeCalled();

        $transformation = new Expression($evaluator->reveal());

        self::assertSame('hi', $transformation->apply('hi', [], $this->item));
    }

    /**
     * @test
     */
    public function it_is_a_no_op_when_the_expression_is_empty(): void
    {
        $evaluator = $this->prophesize(ExpressionEvaluatorInterface::class);
        $evaluator->evaluate(Argument::cetera())->shouldNotBeCalled();

        $transformation = new Expression($evaluator->reveal());

        self::assertSame('hi', $transformation->apply('hi', ['expression' => ''], $this->item));
    }

    /**
     * @test
     */
    public function it_returns_the_value_unchanged_when_evaluation_throws(): void
    {
        $evaluator = $this->prophesize(ExpressionEvaluatorInterface::class);
        $evaluator->evaluate(Argument::cetera())->willThrow(new \RuntimeException('boom'));

        $transformation = new Expression($evaluator->reveal());

        self::assertSame('hi', $transformation->apply('hi', ['expression' => 'invalid('], $this->item));
    }
}
