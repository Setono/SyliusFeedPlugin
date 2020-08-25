<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Doctrine\ORM;

use Doctrine\ORM\QueryBuilder;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Repository\ViolationRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class ViolationRepository extends EntityRepository implements ViolationRepositoryInterface
{
    public function findCountsGroupedBySeverity($feed = null): array
    {
        if ($feed instanceof FeedInterface) {
            $feed = (int) $feed->getId();
        }

        Assert::nullOrInteger($feed);

        $qb = $this->createQueryBuilder('o')
            ->select('NEW Setono\SyliusFeedPlugin\DTO\SeverityCount(o.severity, count(o))')
            ->groupBy('o.severity')
        ;

        if (null !== $feed) {
            $qb->andWhere('o.feed = :feed')
                ->setParameter('feed', $feed);
        }

        return $qb->getQuery()->getResult();
    }

    public function createQueryBuilderByFeed($feed): QueryBuilder
    {
        Assert::scalar($feed);

        return $this->createQueryBuilder('o')
            ->join('o.feed', 'f')
            ->andWhere('f.id = :id')
            ->setParameter('id', $feed)
        ;
    }
}
