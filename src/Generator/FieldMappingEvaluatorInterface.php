<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\FieldDefinition;
use Setono\SyliusFeedPlugin\Mapping\FieldMapping;

/**
 * Applies a source's ordered field mappings to a single item: for each mapping it resolves the
 * source (field/literal/expression/twig), runs the transformation chain, and gates emission on the
 * optional condition — writing surviving values onto the item's bag (§10). Before evaluating, it
 * binds the item's on-demand source resolver so transformations/conditions/expressions can resolve
 * source references by name regardless of mapping order.
 */
interface FieldMappingEvaluatorInterface
{
    /**
     * @param iterable<FieldMapping> $mappings
     * @param array<string, FieldDefinition> $availableFields
     */
    public function apply(FeedItem $item, iterable $mappings, array $availableFields): void;
}
