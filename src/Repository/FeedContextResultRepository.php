<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Repository;

use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class FeedContextResultRepository extends EntityRepository implements FeedContextResultRepositoryInterface
{
    public function findLatestPublished(FeedInterface $feed, string $contextKey): ?FeedContextResultInterface
    {
        $result = $this->createQueryBuilder('o')
            ->andWhere('o.feed = :feed')
            ->andWhere('o.contextKey = :contextKey')
            ->setParameter('feed', $feed)
            ->setParameter('contextKey', $contextKey)
            ->orderBy('o.createdAt', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof FeedContextResultInterface ? $result : null;
    }
}
