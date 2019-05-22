<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataSource;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\EventDispatcher\Event;

final class FilterDataEvent extends Event
{
    public function __construct(QueryBuilder $queryBuilder)
    {
    }
}
