<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver;

use Setono\SyliusFeedPlugin\Registry\Registry;

/**
 * @extends Registry<ValueResolverInterface>
 */
final class ValueResolverRegistry extends Registry implements ValueResolverRegistryInterface
{
    public function get(string $name): ValueResolverInterface
    {
        return $this->getByKey($name);
    }

    /**
     * @param ValueResolverInterface $item
     */
    protected function getKey(object $item): string
    {
        return $item->getName();
    }
}
