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

    /**
     * @param FeedWriterInterface $item
     */
    protected function getKey(object $item): string
    {
        return $item->getFormat();
    }
}
