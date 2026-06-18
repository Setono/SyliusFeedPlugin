<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Lookup;

use Setono\SyliusFeedPlugin\Registry\RegistryInterface;

/**
 * @extends RegistryInterface<LookupSourceInterface>
 */
interface LookupSourceRegistryInterface extends RegistryInterface
{
    public function get(string $type): LookupSourceInterface;
}
