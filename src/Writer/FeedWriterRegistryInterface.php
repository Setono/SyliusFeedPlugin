<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Writer;

interface FeedWriterRegistryInterface
{
    public function get(string $format): FeedWriterInterface;

    public function has(string $format): bool;

    /**
     * @return array<string, FeedWriterInterface>
     */
    public function all(): array;
}
