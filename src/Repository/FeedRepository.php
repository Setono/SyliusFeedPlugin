<?php

declare(strict_types=1);

namespace Loevgaard\SyliusFeedPlugin\Repository;

use Loevgaard\SyliusFeedPlugin\Entity\FeedInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class FeedRepository extends EntityRepository implements FeedRepositoryInterface
{
    /**
     * @param string $uuid
     * @return FeedInterface|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByUuid(string $uuid): ?FeedInterface
    {
        return $this->createQueryBuilder('o')
            ->where('o.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}