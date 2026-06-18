<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedType;

use Setono\SyliusFeedPlugin\Registry\Registry;

/**
 * @extends Registry<FeedTypeInterface>
 */
final class FeedTypeRegistry extends Registry implements FeedTypeRegistryInterface
{
    public function get(string $code): FeedTypeInterface
    {
        return $this->getByKey($code);
    }

    /**
     * @param FeedTypeInterface $item
     */
    protected function getKey(object $item): string
    {
        return $item->getCode();
    }
}
