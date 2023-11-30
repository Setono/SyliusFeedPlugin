<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

/**
 * @internal because it is called in a context where dependent data is updated in the database,
 * i.e. the batches, finished batches numbers in the feed model
 */
class GenerateFeed implements CommandInterface
{
    private int $feedId;

    private int $channelId;

    private int $localeId;

    /**
     * @param int|FeedInterface $feed
     * @param int|ChannelInterface $channel
     * @param int|LocaleInterface $locale
     */
    public function __construct($feed, $channel, $locale)
    {
        $this->setFeedId($feed instanceof FeedInterface ? (int) $feed->getId() : $feed);
        $this->setChannelId($channel instanceof ChannelInterface ? (int) $channel->getId() : $channel);
        $this->setLocaleId($locale instanceof LocaleInterface ? (int) $locale->getId() : $locale);
    }

    public function getFeedId(): int
    {
        return $this->feedId;
    }

    private function setFeedId(int $id): void
    {
        $this->feedId = $id;
    }

    public function getChannelId(): int
    {
        return $this->channelId;
    }

    private function setChannelId(int $id): void
    {
        $this->channelId = $id;
    }

    public function getLocaleId(): int
    {
        return $this->localeId;
    }

    private function setLocaleId(int $id): void
    {
        $this->localeId = $id;
    }
}
