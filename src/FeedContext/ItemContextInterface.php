<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedContext;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

interface ItemContextInterface
{
    /**
     * NOTICE the array returned should be an array of objects normalized
     * This means if you input a single object the return array will be something like
     *
     * [
     *     ['id' => 123, '...' => '...']
     * ]
     *
     * This allows the normalize to add more items to the root which is
     * useful for example in the normalization of products and variants
     */
    public function getContext(object $object, ChannelInterface $channel, LocaleInterface $locale): array;
}
