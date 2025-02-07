<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataMapper;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Specification\Specification;

interface SpecificationDataMapperInterface
{
    public function map(FeedInterface $feed, object $object, Specification $specification): void;
}
