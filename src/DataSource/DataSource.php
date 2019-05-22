<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataSource;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Generator;
use Setono\SyliusFeedPlugin\DataSource\Filter\FilterInterface;
use Setono\SyliusFeedPlugin\SetonoSyliusFeedEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DataSource implements DataSourceInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var string
     */
    private $class;

    /**
     * @var FilterInterface[]
     */
    private $filters = [];

    public function __construct(EventDispatcherInterface $eventDispatcher, ManagerRegistry $managerRegistry, string $class)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->manager = $managerRegistry->getManagerForClass($class);
        $this->class = $class;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $qb = $this->manager->createQueryBuilder();
        $qb->select('o')
            ->from($this->class, 'o');

        foreach ($this->filters as $filter) {
            $filter->filter($qb);
        }

        return $qb;
    }

    public function addFilter(FilterInterface $filter): void
    {
        $this->filters[] = $filter;
    }

    public function getData(): Generator
    {
        $qb = $this->getQueryBuilder();

        $this->eventDispatcher->dispatch(SetonoSyliusFeedEvents::DATA_SOURCE_FILTER_DATA, new FilterDataEvent($qb));

        $result = $qb->getQuery()->iterate();

        foreach ($result as $row) {
            yield $row[0];

            $this->manager->detach($row[0]);
        }
    }
}
