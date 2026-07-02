<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Operator\OperatorInterface;
use Setono\SyliusFeedPlugin\Operator\OperatorRegistryInterface;
use Setono\SyliusFeedPlugin\Reference\ReferenceResolver;
use Setono\SyliusFeedPlugin\Transformation\Conditional;

final class ConditionalTest extends TestCase
{
    use ProphecyTrait;

    private FeedItem $item;

    private function transformation(OperatorRegistryInterface $registry): Conditional
    {
        return new Conditional(new ReferenceResolver(), $registry);
    }

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
    }

    private function registryReturning(string $operatorName, bool $matches): OperatorRegistryInterface
    {
        $operator = $this->prophesize(OperatorInterface::class);
        $operator->matches(Argument::any(), Argument::any())->willReturn($matches);

        $registry = $this->prophesize(OperatorRegistryInterface::class);
        $registry->get($operatorName)->willReturn($operator->reveal());

        return $registry->reveal();
    }

    /**
     * @test
     */
    public function it_has_the_conditional_type(): void
    {
        $registry = $this->prophesize(OperatorRegistryInterface::class)->reveal();
        $transformation = new Conditional(new ReferenceResolver(), $registry);

        self::assertSame('conditional', $transformation->getType());

        $config = Conditional::of(
            ['field' => 'value', 'operator' => 'equals', 'value' => "'used'"],
            ['action' => 'set', 'value' => "'Used'"],
            ['action' => 'continue'],
        );

        self::assertSame('conditional', $config->getType());
        self::assertSame(
            [
                'if' => ['field' => 'value', 'operator' => 'equals', 'value' => "'used'"],
                'then' => ['action' => 'set', 'value' => "'Used'"],
                'else' => ['action' => 'continue'],
            ],
            $config->getParams(),
        );
    }

    /**
     * @test
     */
    public function it_evaluates_the_operator_with_the_resolved_left_and_right_operands(): void
    {
        $operator = $this->prophesize(OperatorInterface::class);
        $operator->matches('used', ['value' => 'used'])->willReturn(true);

        $registry = $this->prophesize(OperatorRegistryInterface::class);
        $registry->get('equals')->willReturn($operator->reveal());

        $transformation = $this->transformation($registry->reveal());

        $result = $transformation->apply('used', [
            'if' => ['field' => 'value', 'operator' => 'equals', 'value' => "'used'"],
            'then' => ['action' => 'set', 'value' => "'Used'"],
        ], $this->item);

        self::assertSame('Used', $result);
    }

    /**
     * @test
     */
    public function it_sets_a_resolved_value_when_the_then_branch_matches(): void
    {
        $registry = $this->registryReturning('equals', true);
        $transformation = $this->transformation($registry);

        $result = $transformation->apply('used', [
            'if' => ['field' => 'value', 'operator' => 'equals', 'value' => "'used'"],
            'then' => ['action' => 'set', 'value' => "'Used'"],
        ], $this->item);

        self::assertSame('Used', $result);
    }

    /**
     * @test
     */
    public function it_can_resolve_the_set_value_from_the_output_bag(): void
    {
        $this->item->set('g:condition_label', 'Refurbished');

        $registry = $this->registryReturning('equals', true);
        $transformation = $this->transformation($registry);

        $result = $transformation->apply('used', [
            'if' => ['field' => 'value', 'operator' => 'equals', 'value' => "'used'"],
            'then' => ['action' => 'set', 'value' => 'g:condition_label'],
        ], $this->item);

        self::assertSame('Refurbished', $result);
    }

    /**
     * @test
     */
    public function it_skips_the_field_returning_null_when_the_action_is_skip(): void
    {
        $registry = $this->registryReturning('equals', true);
        $transformation = $this->transformation($registry);

        $result = $transformation->apply('used', [
            'if' => ['field' => 'value', 'operator' => 'equals', 'value' => "'used'"],
            'then' => ['action' => 'skip'],
        ], $this->item);

        self::assertNull($result);
    }

    /**
     * @test
     */
    public function it_continues_with_the_original_value_when_the_action_is_continue(): void
    {
        $registry = $this->registryReturning('equals', true);
        $transformation = $this->transformation($registry);

        $result = $transformation->apply('used', [
            'if' => ['field' => 'value', 'operator' => 'equals', 'value' => "'used'"],
            'then' => ['action' => 'continue'],
        ], $this->item);

        self::assertSame('used', $result);
    }

    /**
     * @test
     */
    public function it_takes_the_else_branch_when_the_condition_does_not_match(): void
    {
        $registry = $this->registryReturning('equals', false);
        $transformation = $this->transformation($registry);

        $result = $transformation->apply('new', [
            'if' => ['field' => 'value', 'operator' => 'equals', 'value' => "'used'"],
            'then' => ['action' => 'set', 'value' => "'Used'"],
            'else' => ['action' => 'set', 'value' => "'New'"],
        ], $this->item);

        self::assertSame('New', $result);
    }

    /**
     * @test
     */
    public function it_continues_with_the_original_value_when_unmatched_and_there_is_no_else(): void
    {
        $registry = $this->registryReturning('equals', false);
        $transformation = $this->transformation($registry);

        $result = $transformation->apply('new', [
            'if' => ['field' => 'value', 'operator' => 'equals', 'value' => "'used'"],
            'then' => ['action' => 'set', 'value' => "'Used'"],
        ], $this->item);

        self::assertSame('new', $result);
    }

    /**
     * @test
     */
    public function it_is_a_no_op_when_if_or_then_is_missing(): void
    {
        $registry = $this->prophesize(OperatorRegistryInterface::class)->reveal();
        $transformation = $this->transformation($registry);

        self::assertSame('value', $transformation->apply('value', [], $this->item));
        self::assertSame(
            'value',
            $transformation->apply('value', ['if' => ['field' => 'value', 'operator' => 'equals']], $this->item),
        );
    }
}
