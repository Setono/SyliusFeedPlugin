<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Lookup;

use Setono\SyliusFeedPlugin\Model\LookupTableInterface;
use Setono\SyliusFeedPlugin\Repository\LookupTableRepositoryInterface;

/**
 * The production {@see LookupInterface}: resolves against admin-managed {@see LookupTableInterface}
 * resources (§10.1). Each referenced table is loaded once and cached per instance, so a 50k-item
 * run queries a lookup table a single time rather than per row.
 */
final class DatabaseLookup implements LookupInterface
{
    /** @var array<string, LookupTableInterface|null> */
    private array $tables = [];

    public function __construct(private readonly LookupTableRepositoryInterface $repository)
    {
    }

    public function get(string $table, mixed $key, string $column): mixed
    {
        if (!is_scalar($key)) {
            return null;
        }

        return $this->table($table)?->getColumn((string) $key, $column);
    }

    private function table(string $code): ?LookupTableInterface
    {
        if (!array_key_exists($code, $this->tables)) {
            $this->tables[$code] = $this->repository->findOneByCode($code);
        }

        return $this->tables[$code];
    }
}
