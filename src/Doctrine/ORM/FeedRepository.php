<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Doctrine\ORM;

use Doctrine\ORM\NonUniqueResultException;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class FeedRepository extends EntityRepository implements FeedRepositoryInterface
{
    /**
     * @throws NonUniqueResultException
     */
    public function findOneByUuid(string $uuid): ?FeedInterface
    {
        return $this->createQueryBuilder('o')
            ->where('o.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findEnabled(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.enabled = true')
            ->getQuery()
            ->getResult()
        ;
    }

    public function incrementFinishedBatches(FeedInterface $feed): void
    {
        $this->createQueryBuilder('o')
            ->update()
            ->set('o.finishedBatches', 'o.finishedBatches + 1')
            ->where('o.id = :id')
            ->setParameter('id', $feed->getId())
            ->getQuery()
            ->execute()
        ;
    }
}
