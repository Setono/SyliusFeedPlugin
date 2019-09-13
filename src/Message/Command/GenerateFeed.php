<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

/**
 * todo should probably be marked as internal as it may lead to side effects calling this command out of context
 */
final class GenerateFeed implements CommandInterface
{
    /** @var int */
    private $feedId;

    /** @var int */
    private $channelId;

    /** @var int */
    private $localeId;

    /**
     * @param int|FeedInterface $feed
     * @param int|ChannelInterface $channel
     * @param int|LocaleInterface $locale
     */
    public function __construct($feed, $channel, $locale)
    {
        $this->setFeedId($feed instanceof FeedInterface ? $feed->getId() : $feed);
        $this->setChannelId($channel instanceof ChannelInterface ? $channel->getId() : $channel);
        $this->setLocaleId($locale instanceof LocaleInterface ? $locale->getId() : $locale);
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
