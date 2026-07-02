<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Lookup;

/**
 * A lookup facility whose tables live in memory. Empty by default in an application until the M5
 * `LookupTable` resource populates it; usable directly in tests, fixtures and programmatic feeds.
 */
final class InMemoryLookup implements LookupInterface
{
    /** @var array<string, array<string, array<string, mixed>>> table code => join key => (column => value) */
    private array $tables = [];

    /**
     * @param array<string, array<string, mixed>> $rows the table's rows keyed by their join key
     */
    public function addTable(string $table, array $rows): void
    {
        $this->tables[$table] = $rows;
    }

    public function get(string $table, mixed $key, string $column): mixed
    {
        if (!is_scalar($key)) {
            return null;
        }

        return $this->tables[$table][(string) $key][$column] ?? null;
    }
}
