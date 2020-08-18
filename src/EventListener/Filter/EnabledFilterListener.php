<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventListener\Filter;

use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Event\QueryBuilderEvent;
use Sylius\Component\Resource\Model\ToggleableInterface;

final class EnabledFilterListener extends AbstractFilterListener
{
    public function filter(QueryBuilderEvent $event): void
    {
        if (!$this->isEligible($event, [ToggleableInterface::class])) {
            return;
        }

        $classMetadata = $this->getClassMetadata($event);
        if (!$classMetadata->hasField('enabled')) {
            return;
        }

        $qb = $event->getQueryBuilder();

        $alias = $this->getAlias($qb);

        $event->getQueryBuilder()->andWhere(sprintf('%s.enabled = true', $alias));
    }
}
