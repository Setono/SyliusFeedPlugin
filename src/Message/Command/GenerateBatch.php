<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

use Setono\DoctrineORMBatcher\Batch\BatchInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

class GenerateBatch implements CommandInterface
{
    private int $feedId;

    private int $channelId;

    private int $localeId;

    private BatchInterface $batch;

    /**
     * @param int|FeedInterface $feed
     * @param int|ChannelInterface $channel
     * @param int|LocaleInterface $locale
     */
    public function __construct($feed, $channel, $locale, BatchInterface $batch)
    {
        $this->setFeedId($feed instanceof FeedInterface ? (int) $feed->getId() : $feed);
        $this->setChannelId($channel instanceof ChannelInterface ? (int) $channel->getId() : $channel);
        $this->setLocaleId($locale instanceof LocaleInterface ? (int) $locale->getId() : $locale);

        $this->batch = $batch;
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

    public function getBatch(): BatchInterface
    {
        return $this->batch;
    }
}
