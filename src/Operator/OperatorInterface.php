<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Operator;

/**
 * A named predicate evaluated against a value. One operator vocabulary powers all three places a
 * condition is expressed — item filters, field emit-conditions, and `conditional` transforms
 * (§5, §10) — hence the general name rather than a filter-specific one.
 *
 * Collected into the OperatorRegistry via the `setono_sylius_feed.operator` tag.
 */
interface OperatorInterface
{
    /**
     * e.g. "equals", "contains", "in", "gt", "empty", "between".
     */
    public function getName(): string;

    /**
     * @param array<string, mixed> $params
     */
    public function matches(mixed $value, array $params): bool;
}
