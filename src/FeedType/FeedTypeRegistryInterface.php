<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedType;

interface FeedTypeRegistryInterface
{
    public function get(string $code): FeedTypeInterface;

    public function has(string $code): bool;

    /**
     * @return array<string, FeedTypeInterface>
     */
    public function all(): array;
}
