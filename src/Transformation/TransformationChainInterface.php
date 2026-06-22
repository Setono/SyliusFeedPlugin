<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;

interface TransformationChainInterface
{
    /**
     * @param iterable<TransformationConfig> $transformations
     */
    public function apply(mixed $value, iterable $transformations, FeedItem $item): mixed;
}
