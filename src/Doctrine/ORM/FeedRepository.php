<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Doctrine\ORM;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class FeedRepository extends EntityRepository implements FeedRepositoryInterface
{
    public function findOneByCode(string $code): ?FeedInterface
    {
        return $this->createQueryBuilder('o')
            ->where('o.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findReadyToBeProcessed(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.state = :state')
            ->andWhere('o.enabled = true')
            ->setParameter('state', FeedGraph::STATE_READY)
            ->getQuery()
            ->getResult()
        ;
    }

    public function incrementFinishedBatches(FeedInterface $feed): void
    {
        $this->createQueryBuilder('o')
            ->update()
            ->set('o.finishedBatches', 'o.finishedBatches + 1')
            ->andWhere('o.id = :id')
            ->setParameter('id', $feed->getId())
            ->getQuery()
            ->execute()
        ;
    }

    public function batchesGenerated(FeedInterface $feed): bool
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o)')
            ->andWhere('o.id = :id')
            ->andWhere('o.batches = o.finishedBatches')
            ->setParameter('id', $feed->getId())
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;
    }
}
