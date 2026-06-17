<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Filter;

/**
 * A comparison operator shared by filters, field conditions, and `conditional` transforms (§5, §10).
 *
 * Collected into the FilterOperatorRegistry via the `setono_sylius_feed.filter_operator` tag.
 */
interface FilterOperatorInterface
{
    /**
     * e.g. "contains", "in", "gt", "empty".
     */
    public function getName(): string;

    /**
     * @param array<string, mixed> $params
     */
    public function matches(mixed $value, array $params): bool;
}
