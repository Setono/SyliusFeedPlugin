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
use Sylius\Component\Core\Model\ChannelInterface;

/**
 * Streams product variants joined to enabled, channel-assigned products (§8.1). Iterates in
 * bounded-memory batches via {@see BatchIterator} (clears the entity manager every batch), so a
 * large catalog stays within budget even though associations are lazy-loaded per row (§6.3).
 * Query-pushable filters land in M6; for now only the enabled/channel constraints are applied at
 * the query level.
 */
final class ProductVariantDataSource implements DataSourceInterface
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
            $this->createQueryBuilder($context)->getQuery(),
            $this->getManager($this->resourceClass),
            self::BATCH_SIZE,
        );
    }

    public function count(FeedContext $context, FilterSet $filters): int
    {
        return (int) $this->createQueryBuilder($context)
            ->select('COUNT(variant.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getIdRange(FeedContext $context, FilterSet $filters): ?ChunkRange
    {
        return $this->resolveIdRange($this->createQueryBuilder($context), 'variant');
    }

    public function getItemsInRange(FeedContext $context, FilterSet $filters, ChunkRange $range): iterable
    {
        return BatchIterator::iterate(
            $this->constrainToRange($this->createQueryBuilder($context), 'variant', $range)->getQuery(),
            $this->getManager($this->resourceClass),
            self::BATCH_SIZE,
        );
    }

    private function createQueryBuilder(FeedContext $context): QueryBuilder
    {
        $queryBuilder = $this->getManager($this->resourceClass)
            ->getRepository($this->resourceClass)
            ->createQueryBuilder('variant')
            ->innerJoin('variant.product', 'product')
            ->andWhere('product.enabled = true');

        $channel = $context->getChannel();
        if ($channel instanceof ChannelInterface) {
            // MEMBER OF (an EXISTS subquery) rather than a join, since Doctrine's toIterable() does
            // not allow iterating over a query that joins a to-many association.
            $queryBuilder
                ->andWhere(':channel MEMBER OF product.channels')
                ->setParameter('channel', $channel);
        }

        return $queryBuilder;
    }
}
