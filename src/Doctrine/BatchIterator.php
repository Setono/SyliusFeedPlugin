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
 * The algorithm is taken from ocramius/doctrine-batch-utils' SimpleBatchIteratorAggregate:
 *
 * @see https://github.com/Ocramius/DoctrineBatchUtils
 *
 * We deliberately copy the ~15-line algorithm rather than depend on the library because, on the
 * ORM 2.x that Sylius 1.14 pins, the only compatible release is 2.7.0, which (a) caps PHP at 8.3
 * even though this plugin supports 8.4, and (b) tightens its doctrine/orm requirement to ^2.17.3,
 * which destabilises this repo's CI dependency resolution (it downgraded nikic/php-parser into a
 * combination that collides with PHPStan). The versions without those issues require ORM 3. Copying
 * the trivial algorithm avoids the dependency cost entirely.
 *
 * The library's transaction wrapper is intentionally not copied either: feed generation only reads,
 * and binding a transaction to generator consumption would leave a dangling transaction if the
 * consumer stopped iterating early.
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
