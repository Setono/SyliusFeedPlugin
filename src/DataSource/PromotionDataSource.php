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
 * Streams non-archived promotions — one row per promotion (§8.6). A promotion has no
 * enabled/toggle flag of its own; "archived" (§`ArchivableInterface`) is its closest analogue to
 * the enabled guard the other data sources apply, so an archived promotion is excluded. Iterates
 * in bounded-memory batches via {@see BatchIterator} (clears the entity manager every batch).
 */
final class PromotionDataSource implements DataSourceInterface
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
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getIdRange(FeedContext $context, FilterSet $filters): ?ChunkRange
    {
        return $this->resolveIdRange($this->createQueryBuilder(), 'p');
    }

    public function getItemsInRange(FeedContext $context, FilterSet $filters, ChunkRange $range): iterable
    {
        return BatchIterator::iterate(
            $this->constrainToRange($this->createQueryBuilder(), 'p', $range)->getQuery(),
            $this->getManager($this->resourceClass),
            self::BATCH_SIZE,
        );
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return $this->getManager($this->resourceClass)
            ->getRepository($this->resourceClass)
            ->createQueryBuilder('p')
            ->andWhere('p.archivedAt IS NULL');
    }
}
