<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataSource;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Filter\FilterSet;
use Sylius\Component\Core\Model\ChannelInterface;
use Webmozart\Assert\Assert;

/**
 * Streams product variants joined to enabled, channel-assigned products (§8.1). Iterates with
 * Doctrine's {@see \Doctrine\ORM\Query::toIterable()} and clears the entity manager every batch
 * so memory stays bounded for large catalogs (§6.3). Query-pushable filters land in M6; for now
 * only the enabled/channel constraints are applied at the query level.
 */
final class ProductVariantDataSource implements DataSourceInterface
{
    private const BATCH_SIZE = 1000;

    /**
     * @param class-string $resourceClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $resourceClass,
    ) {
    }

    public function getResourceClass(): string
    {
        return $this->resourceClass;
    }

    public function getItems(FeedContext $context, FilterSet $filters): iterable
    {
        $iteration = 0;
        foreach ($this->createQueryBuilder($context)->getQuery()->toIterable() as $variant) {
            Assert::object($variant);

            yield $variant;

            if (0 === (++$iteration % self::BATCH_SIZE)) {
                $this->entityManager->clear();
            }
        }
    }

    public function count(FeedContext $context, FilterSet $filters): int
    {
        return (int) $this->createQueryBuilder($context)
            ->select('COUNT(variant.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createQueryBuilder(FeedContext $context): QueryBuilder
    {
        $queryBuilder = $this->entityManager->getRepository($this->resourceClass)
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
