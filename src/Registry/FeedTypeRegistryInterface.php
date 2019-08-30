<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Registry;

use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;

interface FeedTypeRegistryInterface
{
    public function has(string $code): bool;

    public function get(string $code): FeedTypeInterface;

    public function all(): array;
}
