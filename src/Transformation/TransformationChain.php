<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;

/**
 * Runs a field's ordered transformation chain: each descriptor is resolved to its transformation
 * via the registry and applied in turn (§10). Reference resolution and the full transformation set
 * land in M4; this is the minimal runner used by the generator.
 */
final class TransformationChain
{
    public function __construct(private readonly TransformationRegistryInterface $registry)
    {
    }

    /**
     * @param iterable<TransformationConfig> $transformations
     */
    public function apply(mixed $value, iterable $transformations, FeedItem $item): mixed
    {
        foreach ($transformations as $transformation) {
            $value = $this->registry->get($transformation->getType())->apply($value, $transformation->getParams(), $item);
        }

        return $value;
    }
}
