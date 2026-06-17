<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Writer;

use Setono\SyliusFeedPlugin\Registry\Registry;

/**
 * @extends Registry<FeedWriterInterface>
 */
final class FeedWriterRegistry extends Registry implements FeedWriterRegistryInterface
{
    public function get(string $format): FeedWriterInterface
    {
        return $this->getByKey($format);
    }

    public function has(string $format): bool
    {
        return $this->hasKey($format);
    }

    public function all(): array
    {
        return $this->items();
    }

    /**
     * @param FeedWriterInterface $item
     */
    protected function getKey(object $item): string
    {
        return $item->getFormat();
    }
}
