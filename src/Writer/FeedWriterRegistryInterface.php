<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Writer;

/**
 * @extends \IteratorAggregate<string, FeedWriterInterface>
 */
interface FeedWriterRegistryInterface extends \IteratorAggregate
{
    public function get(string $format): FeedWriterInterface;

    public function has(string $format): bool;

    /**
     * @return array<string, FeedWriterInterface>
     */
    public function all(): array;
}
