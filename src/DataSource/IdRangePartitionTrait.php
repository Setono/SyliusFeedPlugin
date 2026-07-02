<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataSource;

use Doctrine\ORM\QueryBuilder;
use Setono\SyliusFeedPlugin\Generator\ChunkRange;

/**
 * Shared id-range partitioning for the Doctrine-backed data sources (§6.3): resolves the `[min, max]`
 * id bounds of a source's filtered query and constrains a query to a single chunk range, ordered by
 * id, so a fan-out chunk yields exactly its slice and its ordered concatenation matches the inline
 * stream byte-for-byte.
 */
trait IdRangePartitionTrait
{
    /**
     * The inclusive `[min, max]` id bounds of the given query, or null when it would yield nothing.
     */
    private function resolveIdRange(QueryBuilder $queryBuilder, string $alias): ?ChunkRange
    {
        $row = (clone $queryBuilder)
            ->select(sprintf('MIN(%1$s.id) AS min_id, MAX(%1$s.id) AS max_id', $alias))
            ->getQuery()
            ->getSingleResult();

        if (!is_array($row)) {
            return null;
        }

        $min = $row['min_id'] ?? null;
        $max = $row['max_id'] ?? null;

        if (!is_numeric($min) || !is_numeric($max)) {
            return null;
        }

        return new ChunkRange((int) $min, (int) $max);
    }

    /**
     * Constrains the query to a single chunk range, ordered by id ascending.
     */
    private function constrainToRange(QueryBuilder $queryBuilder, string $alias, ChunkRange $range): QueryBuilder
    {
        return $queryBuilder
            ->andWhere(sprintf('%s.id BETWEEN :chunkStart AND :chunkEnd', $alias))
            ->addOrderBy(sprintf('%s.id', $alias), 'ASC')
            ->setParameter('chunkStart', $range->start)
            ->setParameter('chunkEnd', $range->end);
    }
}
