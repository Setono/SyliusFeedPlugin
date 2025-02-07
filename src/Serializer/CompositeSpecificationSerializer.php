<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Serializer;

use Setono\CompositeCompilerPass\CompositeService;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Specification\Specification;

/**
 * @extends CompositeService<SpecificationSerializerInterface>
 */
final class CompositeSpecificationSerializer extends CompositeService implements SpecificationSerializerInterface
{
    public function serialize(FeedInterface $feed, Specification $specification): string
    {
        foreach ($this->services as $service) {
            if ($service->supports($feed, $specification)) {
                return $service->serialize($feed, $specification);
            }
        }

        throw new \RuntimeException('No serializer supports the given feed and specification');
    }

    public function supports(FeedInterface $feed, Specification $specification): bool
    {
        foreach ($this->services as $service) {
            if ($service->supports($feed, $specification)) {
                return true;
            }
        }

        return false;
    }
}
