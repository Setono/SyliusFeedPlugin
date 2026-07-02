<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Lookup;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Setono\SyliusFeedPlugin\Lookup\LookupSourceInterface;
use Setono\SyliusFeedPlugin\Lookup\LookupSourceRegistryInterface;
use Setono\SyliusFeedPlugin\Lookup\LookupTableRefresher;
use Setono\SyliusFeedPlugin\Model\LookupTable;

final class LookupTableRefresherTest extends TestCase
{
    use ProphecyTrait;

    private function table(): LookupTable
    {
        $table = new LookupTable();
        $table->setCode('badges');
        $table->setSourceType('csv');
        $table->setKeyColumn('product_code');

        return $table;
    }

    /**
     * @test
     */
    public function it_imports_rows_keyed_by_the_key_column_and_stamps_refreshed_at(): void
    {
        $source = new class() implements LookupSourceInterface {
            public function getType(): string
            {
                return 'csv';
            }

            public function fetch(array $sourceConfig): iterable
            {
                yield ['product_code' => 'SKU-1', 'gtin' => '111'];
                yield ['product_code' => 'SKU-2', 'gtin' => '222'];
            }
        };

        $registry = $this->prophesize(LookupSourceRegistryInterface::class);
        $registry->has('csv')->willReturn(true);
        $registry->get('csv')->willReturn($source);

        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->flush()->shouldBeCalledOnce();

        $doctrine = $this->prophesize(ManagerRegistry::class);
        $doctrine->getManagerForClass(LookupTable::class)->willReturn($manager->reveal());

        $table = $this->table();
        (new LookupTableRefresher($doctrine->reveal(), $registry->reveal(), $this->prophesize(LoggerInterface::class)->reveal()))->refresh($table);

        self::assertSame([
            'SKU-1' => ['product_code' => 'SKU-1', 'gtin' => '111'],
            'SKU-2' => ['product_code' => 'SKU-2', 'gtin' => '222'],
        ], $table->getRows());
        self::assertNotNull($table->getRefreshedAt());
    }

    /**
     * @test
     */
    public function it_keeps_the_last_good_rows_when_the_source_fails(): void
    {
        $source = new class() implements LookupSourceInterface {
            public function getType(): string
            {
                return 'csv';
            }

            public function fetch(array $sourceConfig): iterable
            {
                throw new \RuntimeException('remote outage');
            }
        };

        $registry = $this->prophesize(LookupSourceRegistryInterface::class);
        $registry->has('csv')->willReturn(true);
        $registry->get('csv')->willReturn($source);

        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->flush()->shouldNotBeCalled();

        $doctrine = $this->prophesize(ManagerRegistry::class);
        $doctrine->getManagerForClass(Argument::any())->willReturn($manager->reveal());

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->warning(Argument::containingString('keeping the last-good'), Argument::cetera())->shouldBeCalled();

        $lastGoodAt = new \DateTimeImmutable('2020-01-01 00:00:00');
        $table = $this->table();
        $table->setRows(['OLD' => ['product_code' => 'OLD', 'gtin' => '999']]);
        $table->setRefreshedAt($lastGoodAt);

        (new LookupTableRefresher($doctrine->reveal(), $registry->reveal(), $logger->reveal()))->refresh($table);

        self::assertSame(['OLD' => ['product_code' => 'OLD', 'gtin' => '999']], $table->getRows());
        self::assertSame($lastGoodAt, $table->getRefreshedAt());
    }
}
