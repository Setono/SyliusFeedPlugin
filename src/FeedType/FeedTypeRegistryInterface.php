<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedType;

use Setono\SyliusFeedPlugin\Registry\RegistryInterface;

/**
 * @extends RegistryInterface<FeedTypeInterface>
 */
interface FeedTypeRegistryInterface extends RegistryInterface
{
    public function get(string $code): FeedTypeInterface;
}
