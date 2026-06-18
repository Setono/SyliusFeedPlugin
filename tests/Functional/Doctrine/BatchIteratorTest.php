<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
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
     * @test
     */
    public function it_streams_all_entities_while_clearing_the_manager_every_batch(): void
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        foreach (['USD', 'EUR', 'DKK'] as $code) {
            $currency = new Currency();
            $currency->setCode($code);
            $manager->persist($currency);
        }
        $manager->flush();

        $query = $manager->createQueryBuilder()
            ->select('currency')
            ->from(Currency::class, 'currency')
            ->getQuery();

        // batch size 1 forces a clear() after every row, proving the cursor keeps streaming and
        // each row is re-fetched as a managed entity across the clears.
        $codes = [];
        foreach (BatchIterator::iterate($query, $manager, 1) as $entity) {
            self::assertInstanceOf(CurrencyInterface::class, $entity);
            $codes[] = $entity->getCode();
        }

        sort($codes);
        self::assertSame(['DKK', 'EUR', 'USD'], $codes);
    }
}
