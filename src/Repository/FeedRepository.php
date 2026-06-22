<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Repository;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class FeedRepository extends EntityRepository implements FeedRepositoryInterface
{
    public function incrementCompletedContexts(FeedInterface $feed): int
    {
        $this->getEntityManager()->createQueryBuilder()
            ->update($this->getClassName(), 'f')
            ->set('f.completedContextCount', 'f.completedContextCount + 1')
            ->where('f.id = :id')
            ->setParameter('id', $feed->getId())
            ->getQuery()
            ->execute();

        return (int) $this->createQueryBuilder('f')
            ->select('f.completedContextCount')
            ->where('f.id = :id')
            ->setParameter('id', $feed->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
