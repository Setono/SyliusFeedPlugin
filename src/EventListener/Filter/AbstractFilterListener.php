<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventListener\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Setono\SyliusFeedPlugin\Event\QueryBuilderEvent;

abstract class AbstractFilterListener
{
    private string $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    protected function isEligible(QueryBuilderEvent $event, array $additionalInstanceChecks = []): bool
    {
        $class = $event->getDataProvider()->getClass();

        if (!is_a($class, $this->class, true)) {
            return false;
        }

        foreach ($additionalInstanceChecks as $additionalInstanceCheck) {
            if (!is_a($class, $additionalInstanceCheck, true)) {
                return false;
            }
        }

        return true;
    }

    protected function getAlias(QueryBuilder $queryBuilder): string
    {
        $aliases = $queryBuilder->getRootAliases();
        if (count($aliases) > 1) {
            throw new InvalidArgumentException('This filter only works with one root alias');
        }

        return $aliases[0];
    }

    protected function getClassMetadata(QueryBuilderEvent $event): ClassMetadata
    {
        $queryBuilder = $event->getQueryBuilder();

        return $queryBuilder->getEntityManager()->getClassMetadata($event->getDataProvider()->getClass());
    }
}
