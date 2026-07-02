<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Filter;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Filter\FilterEvaluator;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Model\FeedFilter;
use Setono\SyliusFeedPlugin\Model\FeedFilterInterface;
use Setono\SyliusFeedPlugin\Operator\Equals;
use Setono\SyliusFeedPlugin\Operator\GreaterThan;
use Setono\SyliusFeedPlugin\Operator\In;
use Setono\SyliusFeedPlugin\Operator\OperatorRegistry;
use Setono\SyliusFeedPlugin\Reference\ReferenceResolver;

/**
 * Include/exclude evaluation over the shared operator vocabulary and the reference-resolution rule
 * (§11): action semantics, first-excluding-wins, and inert (incomplete) filters.
 */
final class FilterEvaluatorTest extends TestCase
{
    /**
     * @test
     */
    public function it_excludes_an_item_when_an_exclude_filter_matches(): void
    {
        $item = $this->item(['availability' => 'out_of_stock']);
        $filter = $this->filter('availability', 'equals', "'out_of_stock'", FeedFilterInterface::ACTION_EXCLUDE);

        self::assertSame($filter, $this->evaluator()->excludedBy($item, [$filter]));
    }

    /**
     * @test
     */
    public function it_keeps_an_item_when_an_exclude_filter_does_not_match(): void
    {
        $item = $this->item(['availability' => 'in_stock']);
        $filter = $this->filter('availability', 'equals', "'out_of_stock'", FeedFilterInterface::ACTION_EXCLUDE);

        self::assertNull($this->evaluator()->excludedBy($item, [$filter]));
    }

    /**
     * @test
     */
    public function it_excludes_an_item_when_an_include_filter_does_not_match(): void
    {
        $item = $this->item(['availability' => 'out_of_stock']);
        $filter = $this->filter('availability', 'equals', "'in_stock'", FeedFilterInterface::ACTION_INCLUDE);

        self::assertSame($filter, $this->evaluator()->excludedBy($item, [$filter]));
    }

    /**
     * @test
     */
    public function it_keeps_an_item_when_an_include_filter_matches(): void
    {
        $item = $this->item(['availability' => 'in_stock']);
        $filter = $this->filter('availability', 'equals', "'in_stock'", FeedFilterInterface::ACTION_INCLUDE);

        self::assertNull($this->evaluator()->excludedBy($item, [$filter]));
    }

    /**
     * @test
     */
    public function it_returns_the_first_excluding_filter(): void
    {
        $item = $this->item(['availability' => 'out_of_stock', 'brand' => 'Acme']);
        $passing = $this->filter('brand', 'equals', "'Acme'", FeedFilterInterface::ACTION_INCLUDE);
        $excluding = $this->filter('availability', 'equals', "'out_of_stock'", FeedFilterInterface::ACTION_EXCLUDE);

        self::assertSame($excluding, $this->evaluator()->excludedBy($item, [$passing, $excluding]));
    }

    /**
     * @test
     */
    public function it_skips_a_filter_with_a_null_operator(): void
    {
        $item = $this->item(['availability' => 'out_of_stock']);

        $filter = new FeedFilter();
        $filter->setField('availability');
        $filter->setOperator(null);
        $filter->setValue("'out_of_stock'");
        $filter->setAction(FeedFilterInterface::ACTION_EXCLUDE);

        self::assertNull($this->evaluator()->excludedBy($item, [$filter]));
    }

    /**
     * A non-string filter value is compared verbatim rather than resolved as a reference.
     *
     * @test
     */
    public function it_uses_a_non_string_value_verbatim(): void
    {
        $item = $this->item(['price' => 100]);

        $filter = new FeedFilter();
        $filter->setField('price');
        $filter->setOperator('gt');
        $filter->setValue(50);
        $filter->setAction(FeedFilterInterface::ACTION_EXCLUDE);

        self::assertSame($filter, $this->evaluator()->excludedBy($item, [$filter]));
    }

    private function evaluator(): FilterEvaluator
    {
        return new FilterEvaluator(
            new ReferenceResolver(),
            new OperatorRegistry([new Equals(), new In(), new GreaterThan()]),
        );
    }

    private function filter(string $field, string $operator, mixed $value, string $action): FeedFilterInterface
    {
        $filter = new FeedFilter();
        $filter->setField($field);
        $filter->setOperator($operator);
        $filter->setValue($value);
        $filter->setAction($action);

        return $filter;
    }

    /**
     * @param array<string, mixed> $source
     */
    private function item(array $source): FeedItem
    {
        $item = new FeedItem(new \stdClass(), new FeedContext());
        $item->setSourceResolver(static fn (string $field): mixed => $source[$field] ?? null);

        return $item;
    }
}
