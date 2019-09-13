<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventListener\Filter;

use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Event\QueryBuilderEvent;
use Sylius\Component\Channel\Model\ChannelsAwareInterface;

final class ChannelFilterListener extends AbstractFilterListener
{
    /**
     * @throws StringsException
     */
    public function filter(QueryBuilderEvent $event): void
    {
        if (!$this->isEligible($event, [ChannelsAwareInterface::class])) {
            return;
        }

        $classMetadata = $this->getClassMetadata($event);
        if (!$classMetadata->hasAssociation('channels')) {
            return;
        }

        $qb = $event->getQueryBuilder();

        $alias = $this->getAlias($qb);

        $qb
            ->andWhere(sprintf(':channel MEMBER OF %s.channels', $alias))
            ->setParameter('channel', $event->getChannel())
        ;
    }
}
