<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Registry;

use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;

interface FeedTypeRegistryInterface
{
    public function has(string $code): bool;

    /**
     * @throw \InvalidArgumentException if a feed type with the given code does not exist
     */
    public function get(string $code): FeedTypeInterface;

    public function all(): array;
}
