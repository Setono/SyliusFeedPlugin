<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Lookup\LookupReferenceResolverInterface;
use Setono\SyliusFeedPlugin\Mapping\FieldDefinition;

/**
 * Binds an item's on-demand source resolver — the closure that resolves a named source field /
 * value resolver (incl. `attribute:` and `lookup:` references) against the item's entity+context
 * (§10). Binding is idempotent (once per item) so it can be established up front by the generator
 * (before pre-filters resolve raw source fields) and re-requested by the mapping evaluator without
 * rebinding. Both the generator and FieldMappingEvaluator::apply() go through here so the closure
 * is built the exact same way regardless of who binds first.
 */
final class SourceResolverBinder
{
    private function __construct()
    {
    }

    /**
     * @param array<string, FieldDefinition> $availableFields
     */
    public static function bind(FeedItem $item, array $availableFields, LookupReferenceResolverInterface $lookupReferenceResolver): void
    {
        if ($item->hasSourceResolver()) {
            return;
        }

        $entity = $item->getEntity();
        $context = $item->getContext();

        $item->setSourceResolver(static function (string $field) use ($entity, $context, $availableFields, $lookupReferenceResolver): mixed {
            if ($lookupReferenceResolver->supports($field)) {
                return $lookupReferenceResolver->resolve($field, $entity, $context, $availableFields);
            }

            return isset($availableFields[$field])
                ? $availableFields[$field]->getResolver()->resolve($entity, $context)
                : null;
        });
    }
}
