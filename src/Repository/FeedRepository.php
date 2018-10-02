<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Repository;

use Setono\SyliusFeedPlugin\Entity\FeedInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class FeedRepository extends EntityRepository implements FeedRepositoryInterface
{
    /**
     * @param string $slug
     *
     * @return FeedInterface|null
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneBySlug(string $slug): ?FeedInterface
    {
        return $this->createQueryBuilder('o')
            ->where('o.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
