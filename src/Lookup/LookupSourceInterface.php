<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Lookup;

/**
 * Imports rows for a LookupTable; one per `source.type` (§5, §10.1).
 *
 * Collected into the LookupSourceRegistry via the `setono_sylius_feed.lookup_source` tag.
 */
interface LookupSourceInterface
{
    /**
     * e.g. "csv", "url", "query".
     */
    public function getType(): string;

    /**
     * Rows (each a column => value map); MUST stream for large sets.
     *
     * @param array<string, mixed> $sourceConfig
     *
     * @return iterable<array<string, scalar|null>>
     */
    public function fetch(array $sourceConfig): iterable;
}
