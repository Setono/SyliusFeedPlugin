<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Template\Context;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

abstract class TemplateContext implements TemplateContextInterface
{
    /**
     * @var FeedInterface
     */
    protected $feed;

    /**
     * @var ChannelInterface
     */
    protected $channel;

    /**
     * @var LocaleInterface
     */
    protected $locale;

    public function setFeed(FeedInterface $feed): void
    {
        $this->feed = $feed;
    }

    public function setChannel(ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }

    public function setLocale(LocaleInterface $locale): void
    {
        $this->locale = $locale;
    }
}
