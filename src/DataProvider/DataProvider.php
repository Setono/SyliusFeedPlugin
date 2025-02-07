<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;

// todo clear entity manager. Look at what https://github.com/Ocramius/DoctrineBatchUtils/blob/2.12.x/src/DoctrineBatchUtils/BatchProcessing/SelectBatchIteratorAggregate.php does
final class DataProvider implements DataProviderInterface
{
    use ORMTrait;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function getIds(string $entity): iterable
    {
        return $this->getManager($entity)
            ->createQueryBuilder()
            ->select('o.id') // todo get identifier from metadata
            ->from($entity, 'o')
            ->getQuery()
            ->toIterable()
        ;
    }

    public function getObjects(string $entity, array $ids): iterable
    {
        return $this->getManager($entity)
            ->createQueryBuilder()
            ->select('o')
            ->from($entity, 'o')
            ->andWhere('o.id IN (:ids)') // todo get identifier from metadata
            ->setParameter('ids', $ids)
            ->getQuery()
            ->toIterable()
        ;
    }
}
