<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Serializer;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Specification\Specification;

interface SpecificationSerializerInterface
{
    public function serialize(FeedInterface $feed, Specification $specification): string;
}
