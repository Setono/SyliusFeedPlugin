<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Webmozart\Assert\Assert;

/**
 * Streams a query's entities for read-only processing: each row is re-fetched so it is managed, and
 * the entity manager is cleared every $batchSize rows (and once more at the end) so memory stays
 * bounded even when associations are lazy-loaded per row — no eager join is required to process a
 * large catalog.
 *
 * This mirrors the read-focused variant of ocramius/doctrine-batch-utils, SelectBatchIteratorAggregate:
 *
 * @see https://github.com/Ocramius/DoctrineBatchUtils/blob/2.13.x/src/DoctrineBatchUtils/BatchProcessing/SelectBatchIteratorAggregate.php
 *
 * We copy the ~15-line algorithm rather than depend on the library because, on the ORM 2.x that
 * Sylius 1.14 pins, the only compatible release is 2.7.0, which (a) caps PHP at 8.3 even though this
 * plugin supports 8.4, and (b) tightens its doctrine/orm requirement to ^2.17.3, which destabilises
 * this repo's CI dependency resolution. The releases without those issues require ORM 3. The
 * "select" variant carries no flush and no transaction — exactly what read-only feed generation
 * needs.
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

        $manager->clear();
    }

    private static function reFetch(EntityManagerInterface $manager, object $entity): object
    {
        $class = $entity::class;
        $identifier = $manager->getClassMetadata($class)->getIdentifierValues($entity);

        $fresh = $manager->find($class, $identifier);
        Assert::notNull($fresh, sprintf('Could not re-fetch %s while batch-iterating; was it removed mid-iteration?', $class));

        return $fresh;
    }
}
