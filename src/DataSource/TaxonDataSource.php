<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataSource;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Doctrine\BatchIterator;
use Setono\SyliusFeedPlugin\Filter\FilterSet;
use Setono\SyliusFeedPlugin\Generator\ChunkRange;

/**
 * Streams enabled taxons — one row per taxon (§8.4), scoped over locale only: a taxon is not
 * channel- or currency-specific, so unlike the product data sources this applies no channel
 * constraint at all — another proof point that a data source's query shape is driven purely by
 * its own resource, not by a fixed set of dimensions the engine imposes (§6.3). Iterates in
 * bounded-memory batches via {@see BatchIterator} (clears the entity manager every batch).
 */
final class TaxonDataSource implements DataSourceInterface
{
    use ORMTrait;
    use IdRangePartitionTrait;

    private const BATCH_SIZE = 1000;

    /**
     * @param class-string $resourceClass
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly string $resourceClass,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function getResourceClass(): string
    {
        return $this->resourceClass;
    }

    public function getItems(FeedContext $context, FilterSet $filters): iterable
    {
        return BatchIterator::iterate(
            $this->createQueryBuilder()->getQuery(),
            $this->getManager($this->resourceClass),
            self::BATCH_SIZE,
        );
    }

    public function count(FeedContext $context, FilterSet $filters): int
    {
        return (int) $this->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getIdRange(FeedContext $context, FilterSet $filters): ?ChunkRange
    {
        return $this->resolveIdRange($this->createQueryBuilder(), 't');
    }

    public function getItemsInRange(FeedContext $context, FilterSet $filters, ChunkRange $range): iterable
    {
        return BatchIterator::iterate(
            $this->constrainToRange($this->createQueryBuilder(), 't', $range)->getQuery(),
            $this->getManager($this->resourceClass),
            self::BATCH_SIZE,
        );
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return $this->getManager($this->resourceClass)
            ->getRepository($this->resourceClass)
            ->createQueryBuilder('t')
            ->andWhere('t.enabled = true');
    }
}
