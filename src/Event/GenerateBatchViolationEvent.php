<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Event;

use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class GenerateBatchViolationEvent
{
    /**
     * @param object|array $item
     */
    public function __construct(
        private readonly FeedInterface $feed,
        private readonly FeedTypeInterface $feedType,
        private readonly ChannelInterface $channel,
        private readonly LocaleInterface $locale,
        private $item,
        private readonly ConstraintViolationListInterface $constraintViolationList,
    ) {
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

    public function getConstraintViolationList(): ConstraintViolationListInterface
    {
        return $this->constraintViolationList;
    }
}
