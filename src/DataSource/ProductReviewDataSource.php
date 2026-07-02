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
use Sylius\Component\Review\Model\ReviewInterface;

/**
 * Streams accepted product reviews — one row per review (§8.5). Only reviews with status
 * `accepted` are included; a review that is still new or has been rejected never reaches a feed.
 * Iterates in bounded-memory batches via {@see BatchIterator} (clears the entity manager every
 * batch), so a large review history stays within budget (§6.3).
 */
final class ProductReviewDataSource implements DataSourceInterface
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
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getIdRange(FeedContext $context, FilterSet $filters): ?ChunkRange
    {
        return $this->resolveIdRange($this->createQueryBuilder(), 'r');
    }

    public function getItemsInRange(FeedContext $context, FilterSet $filters, ChunkRange $range): iterable
    {
        return BatchIterator::iterate(
            $this->constrainToRange($this->createQueryBuilder(), 'r', $range)->getQuery(),
            $this->getManager($this->resourceClass),
            self::BATCH_SIZE,
        );
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return $this->getManager($this->resourceClass)
            ->getRepository($this->resourceClass)
            ->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', ReviewInterface::STATUS_ACCEPTED);
    }
}
