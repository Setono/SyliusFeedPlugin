<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Webmozart\Assert\Assert;

/**
 * Streams a query's entities, clearing the entity manager every $batchSize rows so memory stays
 * bounded even when associations are lazy-loaded per row — no eager join is required to process a
 * large catalog. This is the documented Doctrine batch-processing pattern (Query::toIterable() +
 * EntityManager::clear()).
 *
 * The approach mirrors ocramius/doctrine-batch-utils' SimpleBatchIteratorAggregate:
 *
 * @see https://github.com/Ocramius/DoctrineBatchUtils
 *
 * We copy the algorithm rather than depend on the library because, on the ORM 2.x that Sylius 1.14
 * pins, the only compatible release is 2.7.0, which (a) caps PHP at 8.3 even though this plugin
 * supports 8.4, and (b) tightens its doctrine/orm requirement to ^2.17.3, which destabilises this
 * repo's CI dependency resolution (it downgraded nikic/php-parser into a combination that collides
 * with PHPStan). The releases without those issues require ORM 3.
 *
 * Two parts of the library are intentionally left out: the transaction wrapper (feed generation
 * only reads, and binding a transaction to generator consumption would leave a dangling transaction
 * if the consumer stopped iterating early) and the per-row re-fetch (it guards the library's
 * array-input mode; rows produced by toIterable() are already managed).
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

            yield $entity;

            if (0 === (++$iteration % $batchSize)) {
                $manager->clear();
            }
        }
    }
}
