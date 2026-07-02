<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Audit;

/**
 * A purely advisory quality report over the items a preview would include (§11): it excludes
 * nothing, it only surfaces per-field fill rates, non-blocking soft warnings, and value
 * distributions so an admin can spot data-quality issues before shipping the feed.
 */
final class AuditReport
{
    /**
     * @param array<string, float> $fillRates output field => fraction (0..1) of included items where the field is non-empty
     * @param list<array{type: string, field: string, count: int}> $warnings advisory soft warnings, each with the number of affected items
     * @param array<string, array<string, int>> $distributions output field => value => count
     */
    public function __construct(
        public readonly array $fillRates,
        public readonly array $warnings,
        public readonly array $distributions,
    ) {
    }
}
