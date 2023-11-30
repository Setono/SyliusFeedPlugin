<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventListener\Filter;

use Setono\SyliusFeedPlugin\Event\QueryBuilderEvent;
use Sylius\Component\Inventory\Model\StockableInterface;

class InStockFilterListener extends AbstractFilterListener
{
    public function filter(QueryBuilderEvent $event): void
    {
        if (!$this->isEligible($event, [StockableInterface::class])) {
            return;
        }

        if (!$this->getClassMetadata($event)->hasField('onHand')) {
            return;
        }

        $qb = $event->getQueryBuilder();

        $alias = $this->getAlias($qb);

        $event->getQueryBuilder()->andWhere(sprintf('%s.onHand > 0', $alias));
    }
}
