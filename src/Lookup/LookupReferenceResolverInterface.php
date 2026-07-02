<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Lookup;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldDefinition;

/**
 * Resolves a `lookup:{code}:{column}` source reference for an item (§10.1): it reads the table's
 * `joinField` value off the item (via the source resolvers), matches it against the table, and
 * returns the requested column — so a mapping like `concat(["value", "lookup:badges:badge"])`
 * enriches each row per item.
 */
interface LookupReferenceResolverInterface
{
    public function supports(string $reference): bool;

    /**
     * @param array<string, FieldDefinition> $availableFields
     */
    public function resolve(string $reference, object $entity, FeedContext $context, array $availableFields): mixed;
}
