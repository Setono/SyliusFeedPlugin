<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\DoctrineORMBatcher\Batch\BatchInterface;
use Setono\DoctrineORMBatcher\Batch\CollectionBatchInterface;
use Setono\DoctrineORMBatcher\Batcher\BatcherInterface;
use Setono\DoctrineORMBatcher\Factory\BatcherFactoryInterface;
use Setono\DoctrineORMBatcher\Query\QueryRebuilderInterface;

class DataProvider implements DataProviderInterface
{
    private const BATCH_SIZE = 100;

    /** @var BatcherFactoryInterface */
    private $batcherFactory;

    /** @var QueryRebuilderInterface */
    private $queryRebuilder;

    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var string */
    private $class;

    /** @var BatcherInterface */
    private $batcher;

    public function __construct(
        BatcherFactoryInterface $batcherFactory,
        QueryRebuilderInterface $queryRebuilder,
        ManagerRegistry $managerRegistry,
        string $class
    ) {
        $this->batcherFactory = $batcherFactory;
        $this->queryRebuilder = $queryRebuilder;
        $this->managerRegistry = $managerRegistry;
        $this->class = $class;
    }

    /**
     * @return iterable<CollectionBatchInterface>
     *
     * @throws StringsException
     */
    public function getBatches(): iterable
    {
        yield from $this->getBatcher()->getBatches(self::BATCH_SIZE);
    }

    /**
     * @throws StringsException
     */
    public function getBatchCount(): int
    {
        return $this->getBatcher()->getBatchCount(self::BATCH_SIZE);
    }

    public function getItems(BatchInterface $batch): iterable
    {
        $q = $this->queryRebuilder->rebuild($batch);

        return $q->getResult();
    }

    /**
     * @throws StringsException
     */
    private function getQueryBuilder(): QueryBuilder
    {
        $manager = $this->getManager();
        $qb = $manager->createQueryBuilder();
        $qb->select('o')
            ->from($this->class, 'o');

        // todo fire event to let users filter the $qb

        return $qb;
    }

    /**
     * @throws StringsException
     */
    private function getManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface|null $manager */
        $manager = $this->managerRegistry->getManagerForClass($this->class);

        if (null === $manager) {
            throw new InvalidArgumentException(sprintf('No manager for class %s', $this->class));
        }

        return $manager;
    }

    /**
     * @throws StringsException
     */
    private function getBatcher(): BatcherInterface
    {
        if (null === $this->batcher) {
            $this->batcher = $this->batcherFactory->createIdCollectionBatcher($this->getQueryBuilder());
        }

        return $this->batcher;
    }
}
