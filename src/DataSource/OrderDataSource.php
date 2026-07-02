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
use Sylius\Component\Order\Model\OrderInterface;

/**
 * Streams completed orders — one row per order (§8.2). "Completed" excludes carts (state !=
 * `cart`); an order in any other state (new, cancelled, fulfilled, …) is included, since a CSV
 * export is typically interested in the full order history, not just fulfilled ones. Iterates in
 * bounded-memory batches via {@see BatchIterator} (clears the entity manager every batch), proving
 * the same engine that streams a product catalog streams an order export just as well (§6.3).
 */
final class OrderDataSource implements DataSourceInterface
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
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getIdRange(FeedContext $context, FilterSet $filters): ?ChunkRange
    {
        return $this->resolveIdRange($this->createQueryBuilder($context), 'o');
    }

    public function getItemsInRange(FeedContext $context, FilterSet $filters, ChunkRange $range): iterable
    {
        return BatchIterator::iterate(
            $this->constrainToRange($this->createQueryBuilder($context), 'o', $range)->getQuery(),
            $this->getManager($this->resourceClass),
            self::BATCH_SIZE,
        );
    }

    private function createQueryBuilder(FeedContext $context): QueryBuilder
    {
        $queryBuilder = $this->getManager($this->resourceClass)
            ->getRepository($this->resourceClass)
            ->createQueryBuilder('o')
            ->andWhere('o.state != :cartState')
            ->setParameter('cartState', OrderInterface::STATE_CART);

        $channel = $context->getChannel();
        if ($channel instanceof ChannelInterface) {
            $queryBuilder
                ->andWhere('o.channel = :channel')
                ->setParameter('channel', $channel);
        }

        return $queryBuilder;
    }
}
