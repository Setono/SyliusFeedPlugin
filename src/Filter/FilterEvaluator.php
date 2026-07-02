<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Filter;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Model\FeedFilterInterface;
use Setono\SyliusFeedPlugin\Operator\OperatorRegistryInterface;
use Setono\SyliusFeedPlugin\Reference\ReferenceResolverInterface;

/**
 * Include/exclude items by (field, operator, value, action) over the shared operator vocabulary
 * and the one reference-resolution rule (§10, §11).
 *
 * Both operands are references: the `field` resolves against the item, and a string `value` is
 * resolved the same way (so it can point at another field / an earlier output key / a quoted
 * literal); a non-string `value` is used verbatim. `exclude` drops the item when the operator
 * matches; `include` keeps only matching items (so a non-match excludes).
 *
 * NOTE: filters are evaluated per item. Pushing `pre` filters down into the data source's query
 * (so the database never returns rows that would be filtered out) is a future optimization.
 */
final class FilterEvaluator implements FilterEvaluatorInterface
{
    public function __construct(
        private readonly ReferenceResolverInterface $referenceResolver,
        private readonly OperatorRegistryInterface $operatorRegistry,
    ) {
    }

    public function excludedBy(FeedItem $item, iterable $filters): ?FeedFilterInterface
    {
        foreach ($filters as $filter) {
            if ($this->excludes($filter, $item)) {
                return $filter;
            }
        }

        return null;
    }

    private function excludes(FeedFilterInterface $filter, FeedItem $item): bool
    {
        $field = $filter->getField();
        $operator = $filter->getOperator();

        // An incomplete filter (no field/operator) is inert — it never excludes anything.
        if (null === $field || null === $operator) {
            return false;
        }

        $left = $this->referenceResolver->resolve($field, null, $item);

        $rawValue = $filter->getValue();
        $operand = is_string($rawValue) ? $this->referenceResolver->resolve($rawValue, null, $item) : $rawValue;

        $matched = $this->operatorRegistry->get($operator)->matches($left, ['value' => $operand]);

        return FeedFilterInterface::ACTION_EXCLUDE === $filter->getAction() ? $matched : !$matched;
    }
}
