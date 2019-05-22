<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataSource;

use Generator;
use Setono\SyliusFeedPlugin\DataSource\Filter\FilterInterface;

interface DataSourceInterface
{
    public function addFilter(FilterInterface $filter): void;

    public function getData(): Generator;
}
