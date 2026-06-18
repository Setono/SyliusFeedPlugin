<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Webmozart\Assert\Assert;

/**
 * Streams a query's entities in batches, clearing the entity manager every $batchSize rows so that
 * memory stays bounded even when associations are lazy-loaded per row — no eager join is required
 * to process a large catalog. Each row is re-fetched by its identifier so it stays managed across
 * the periodic clear() (lazy associations keep resolving after the identity map is emptied).
 *
 * Adapted from ocramius/doctrine-batch-utils (SimpleBatchIteratorAggregate); reimplemented here to
 * avoid that package's ORM-3 / PHP-8.4 constraints. The transaction wrapper is intentionally not
 * copied: feed generation only reads, and binding a transaction to generator consumption would
 * leave a dangling transaction if the consumer stopped iterating early.
 */
final class BatchIterator
{
    /**
     * @return iterable<object>
     */
    public static function iterate(Query $query, EntityManagerInterface $manager, int $batchSize): iterable
    {
        Assert::positiveInteger($batchSize);

        $iteration = 0;
        foreach ($query->toIterable() as $entity) {
            Assert::object($entity);

            yield self::reFetch($manager, $entity);

            if (0 === (++$iteration % $batchSize)) {
                $manager->clear();
            }
        }
    }

    private static function reFetch(EntityManagerInterface $manager, object $entity): object
    {
        $class = $entity::class;
        $identifier = $manager->getClassMetadata($class)->getIdentifierValues($entity);

        return $manager->find($class, $identifier) ?? $entity;
    }
}
