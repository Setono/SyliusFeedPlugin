<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Lookup;

/**
 * Joins an item against an external enrichment table (§10.1): given a table code, a join key and a
 * column, return the stored value — or null on a miss (never an error). Backs both the
 * `lookup:{table}:{column}` source reference and the `lookup(table, key, column)` scripting function.
 *
 * M4 ships an in-memory implementation ({@see InMemoryLookup}); the refreshable admin-managed
 * `LookupTable` resource (csv/url sources, keep-last-good refresh) lands in M5.
 */
interface LookupInterface
{
    public function get(string $table, mixed $key, string $column): mixed;
}
