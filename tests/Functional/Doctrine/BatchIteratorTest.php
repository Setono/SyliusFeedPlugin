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
     * The memory guarantee: a just-yielded row is managed, but once a batch boundary is crossed the
     * manager is cleared, so the earlier row is detached (the identity map does not keep growing).
     *
     * @test
     */
    public function it_detaches_managed_entities_at_each_batch_boundary(): void
    {
        $manager = $this->createCurrencies('USD', 'EUR', 'DKK');

        $first = null;
        $iteration = 0;
        foreach (BatchIterator::iterate($this->query($manager, 'USD', 'EUR', 'DKK'), $manager, 1) as $entity) {
            ++$iteration;

            if (1 === $iteration) {
                $first = $entity;
                self::assertTrue($manager->contains($first), 'a just-yielded row must be managed');
            }

            if (2 === $iteration) {
                self::assertNotNull($first);
                self::assertFalse($manager->contains($first), 'the earlier row must be detached after the batch-boundary clear()');
            }
        }
    }

    /**
     * The complementary branch: below the batch size no clear() happens, so earlier rows stay
     * managed while iterating.
     *
     * @test
     */
    public function it_keeps_entities_managed_within_a_batch(): void
    {
        $manager = $this->createCurrencies('USD', 'EUR', 'DKK');

        $first = null;
        $iteration = 0;
        foreach (BatchIterator::iterate($this->query($manager, 'USD', 'EUR', 'DKK'), $manager, 10) as $entity) {
            ++$iteration;

            if (1 === $iteration) {
                $first = $entity;
            }

            if (3 === $iteration) {
                self::assertNotNull($first);
                self::assertTrue($manager->contains($first), 'rows must stay managed until the batch size is reached');
            }
        }
    }

    /**
     * @test
     */
    public function it_yields_nothing_for_an_empty_result_set(): void
    {
        $manager = $this->entityManager();

        $results = [];
        foreach (BatchIterator::iterate($this->query($manager, 'XYZ'), $manager, 5) as $entity) {
            $results[] = $entity;
        }

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
