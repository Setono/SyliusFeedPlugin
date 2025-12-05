<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Doctrine\ORM;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class FeedRepository extends EntityRepository implements FeedRepositoryInterface
{
    public function findOneByCode(string $code): ?FeedInterface
    {
        $result = $this->createQueryBuilder('o')
            ->where('o.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        Assert::nullOrIsInstanceOf($result, FeedInterface::class);

        return $result;
    }

    public function findEnabled(): array
    {
        $res = $this->createQueryBuilder('o')
            ->where('o.enabled = true')
            ->getQuery()
            ->getResult()
        ;

        Assert::isArray($res);
        Assert::allIsInstanceOf($res, FeedInterface::class);

        return $res;
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
