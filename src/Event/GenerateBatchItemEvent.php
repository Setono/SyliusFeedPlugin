<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Event;

use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

class GenerateBatchItemEvent
{
    private FeedInterface $feed;

    private FeedTypeInterface $feedType;

    private ChannelInterface $channel;

    private LocaleInterface $locale;

    /** @var object|array */
    private $item;

    /**
     * @param object|array $item
     */
    public function __construct(FeedInterface $feed, FeedTypeInterface $feedType, ChannelInterface $channel, LocaleInterface $locale, $item)
    {
        $this->feed = $feed;
        $this->feedType = $feedType;
        $this->channel = $channel;
        $this->locale = $locale;
        $this->item = $item;
    }

    public function getFeed(): FeedInterface
    {
        return $this->feed;
    }

    public function getFeedType(): FeedTypeInterface
    {
        return $this->feedType;
    }

    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }

    public function getLocale(): LocaleInterface
    {
        return $this->locale;
    }

    /**
     * @return array|object
     */
    public function getItem()
    {
        return $this->item;
    }
}
