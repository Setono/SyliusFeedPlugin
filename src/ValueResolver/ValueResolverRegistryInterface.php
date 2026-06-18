<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver;

use Setono\SyliusFeedPlugin\Registry\RegistryInterface;

/**
 * @extends RegistryInterface<ValueResolverInterface>
 */
interface ValueResolverRegistryInterface extends RegistryInterface
{
    public function get(string $name): ValueResolverInterface;
}
