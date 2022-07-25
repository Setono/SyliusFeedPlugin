<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\DoctrineORMBatcher\Batch\BatchInterface;
use Setono\DoctrineORMBatcher\Batch\CollectionBatchInterface;
use Setono\DoctrineORMBatcher\Batcher\Collection\CollectionBatcherInterface;
use Setono\DoctrineORMBatcher\Factory\BatcherFactoryInterface;
use Setono\DoctrineORMBatcher\Query\QueryRebuilderInterface;
use Setono\SyliusFeedPlugin\Event\QueryBuilderEvent;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

class DataProvider implements DataProviderInterface
{
    private const BATCH_SIZE = 100;

    private BatcherFactoryInterface $batcherFactory;

    private QueryRebuilderInterface $queryRebuilder;

    private EventDispatcherInterface $eventDispatcher;

    private ManagerRegistry $managerRegistry;

    private string $class;

    /** @var CollectionBatcherInterface[] */
    private array $batchers = [];

    public function __construct(
        BatcherFactoryInterface $batcherFactory,
        QueryRebuilderInterface $queryRebuilder,
        EventDispatcherInterface $eventDispatcher,
        ManagerRegistry $managerRegistry,
        string $class
    ) {
        $this->batcherFactory = $batcherFactory;
        $this->queryRebuilder = $queryRebuilder;
        $this->eventDispatcher = $eventDispatcher;
        $this->managerRegistry = $managerRegistry;
        $this->class = $class;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return iterable<CollectionBatchInterface>
     */
    public function getBatches(ChannelInterface $channel, LocaleInterface $locale): iterable
    {
        yield from $this->getBatcher($channel, $locale)->getBatches(self::BATCH_SIZE);
    }

    public function getBatchCount(ChannelInterface $channel, LocaleInterface $locale): int
    {
        return $this->getBatcher($channel, $locale)->getBatchCount(self::BATCH_SIZE);
    }

    public function getItems(BatchInterface $batch): iterable
    {
        $q = $this->queryRebuilder->rebuild($batch);

        return $q->getResult();
    }

    private function getQueryBuilder(ChannelInterface $channel, LocaleInterface $locale): QueryBuilder
    {
        $manager = $this->getManager();
        $qb = $manager->createQueryBuilder();
        $qb->select('o')
            ->from($this->class, 'o');

        $this->eventDispatcher->dispatch(new QueryBuilderEvent($this, $qb, $channel, $locale));

        return $qb;
    }

    private function getManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface|null $manager */
        $manager = $this->managerRegistry->getManagerForClass($this->class);

        if (null === $manager) {
            throw new InvalidArgumentException(sprintf('No manager for class %s', $this->class));
        }

        return $manager;
    }

    private function getBatcher(ChannelInterface $channel, LocaleInterface $locale): CollectionBatcherInterface
    {
        $key = $channel->getCode() . $locale->getCode();
        if (!isset($this->batchers[$key])) {
            $this->batchers[$key] = $this->batcherFactory->createIdCollectionBatcher($this->getQueryBuilder($channel, $locale));
        }

        return $this->batchers[$key];
    }
}
