<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DataMapper;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Specification\Specification;

final class SpecificationDataMapper implements SpecificationDataMapperInterface
{
    public function map(FeedInterface $feed, object $object, Specification $specification): void
    {
        // todo
    }
}
