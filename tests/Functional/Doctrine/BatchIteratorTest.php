<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Setono\SyliusFeedPlugin\Doctrine\BatchIterator;
use Setono\SyliusFeedPlugin\Tests\Functional\FunctionalTestCase;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Currency\Model\CurrencyInterface;

/**
 * @covers \Setono\SyliusFeedPlugin\Doctrine\BatchIterator
 */
final class BatchIteratorTest extends FunctionalTestCase
{
    /**
     * The whole point: clearing the manager mid-stream must not break the cursor, so every row is
     * still yielded across several batch boundaries.
     *
     * @test
     */
    public function it_streams_every_row_across_multiple_batches(): void
    {
        $manager = $this->createCurrencies('USD', 'EUR', 'DKK', 'GBP', 'SEK');

        $codes = [];
        foreach (BatchIterator::iterate($this->query($manager, 'USD', 'EUR', 'DKK', 'GBP', 'SEK'), $manager, 2) as $entity) {
            self::assertInstanceOf(CurrencyInterface::class, $entity);
            $codes[] = $entity->getCode();
        }

        sort($codes);
        self::assertSame(['DKK', 'EUR', 'GBP', 'SEK', 'USD'], $codes);
    }

    /**
     * Proves the memory guarantee: once a batch boundary is crossed the manager is cleared, so the
     * yielded entities are detached afterwards (the identity map does not keep growing).
     *
     * @test
     */
    public function it_clears_the_manager_at_each_batch_boundary(): void
    {
        $manager = $this->createCurrencies('USD', 'EUR', 'NOK');

        $entities = [];
        foreach (BatchIterator::iterate($this->query($manager, 'USD', 'EUR', 'NOK'), $manager, 1) as $entity) {
            $entities[] = $entity;
        }

        self::assertCount(3, $entities);
        foreach ($entities as $entity) {
            self::assertFalse($manager->contains($entity), 'Entity should be detached after a batch-boundary clear()');
        }
    }

    /**
     * The complementary branch: below the batch size no clear() happens, so entities stay managed.
     *
     * @test
     */
    public function it_keeps_entities_managed_below_the_batch_size(): void
    {
        $manager = $this->createCurrencies('USD', 'EUR', 'NOK');

        $entities = [];
        foreach (BatchIterator::iterate($this->query($manager, 'USD', 'EUR', 'NOK'), $manager, 1000) as $entity) {
            $entities[] = $entity;
        }

        self::assertCount(3, $entities);
        foreach ($entities as $entity) {
            self::assertTrue($manager->contains($entity), 'Entity should stay managed when the batch size is not reached');
        }
    }

    /**
     * @test
     */
    public function it_yields_nothing_for_an_empty_result_set(): void
    {
        $manager = $this->entityManager();

        $results = iterator_to_array(BatchIterator::iterate($this->query($manager, 'XYZ'), $manager, 5));

        self::assertSame([], $results);
    }

    private function entityManager(): EntityManagerInterface
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
    }

    private function createCurrencies(string ...$codes): EntityManagerInterface
    {
        $manager = $this->entityManager();

        foreach ($codes as $code) {
            $currency = new Currency();
            $currency->setCode($code);
            $manager->persist($currency);
        }
        $manager->flush();

        return $manager;
    }

    private function query(EntityManagerInterface $manager, string ...$codes): Query
    {
        return $manager->createQueryBuilder()
            ->select('currency')
            ->from(Currency::class, 'currency')
            ->where('currency.code IN (:codes)')
            ->setParameter('codes', $codes)
            ->getQuery();
    }
}
