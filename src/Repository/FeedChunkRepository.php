<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Repository;

use Setono\SyliusFeedPlugin\Model\FeedChunkInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class FeedChunkRepository extends EntityRepository implements FeedChunkRepositoryInterface
{
    public function markCompleted(FeedInterface $feed, string $contextKey, int $chunkIndex): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->update($this->getClassName(), 'c')
            ->set('c.completed', ':completed')
            ->where('c.feed = :feed')
            ->andWhere('c.contextKey = :contextKey')
            ->andWhere('c.chunkIndex = :chunkIndex')
            ->andWhere('c.completed = :pending')
            ->setParameter('completed', true)
            ->setParameter('pending', false)
            ->setParameter('feed', $feed)
            ->setParameter('contextKey', $contextKey)
            ->setParameter('chunkIndex', $chunkIndex)
            ->getQuery()
            ->execute();
    }

    public function allCompleted(FeedInterface $feed, string $contextKey): bool
    {
        $total = (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.feed = :feed')
            ->andWhere('c.contextKey = :contextKey')
            ->setParameter('feed', $feed)
            ->setParameter('contextKey', $contextKey)
            ->getQuery()
            ->getSingleScalarResult();

        if (0 === $total) {
            return false;
        }

        $completed = (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.feed = :feed')
            ->andWhere('c.contextKey = :contextKey')
            ->andWhere('c.completed = :completed')
            ->setParameter('feed', $feed)
            ->setParameter('contextKey', $contextKey)
            ->setParameter('completed', true)
            ->getQuery()
            ->getSingleScalarResult();

        return $completed === $total;
    }

    public function findForContext(FeedInterface $feed, string $contextKey): array
    {
        /** @var list<FeedChunkInterface> $chunks */
        $chunks = $this->createQueryBuilder('c')
            ->andWhere('c.feed = :feed')
            ->andWhere('c.contextKey = :contextKey')
            ->setParameter('feed', $feed)
            ->setParameter('contextKey', $contextKey)
            ->orderBy('c.chunkIndex', 'ASC')
            ->getQuery()
            ->getResult();

        return $chunks;
    }

    public function deleteForContext(FeedInterface $feed, string $contextKey): int
    {
        $affected = $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getClassName(), 'c')
            ->where('c.feed = :feed')
            ->andWhere('c.contextKey = :contextKey')
            ->setParameter('feed', $feed)
            ->setParameter('contextKey', $contextKey)
            ->getQuery()
            ->execute();

        return is_int($affected) ? $affected : 0;
    }
}
