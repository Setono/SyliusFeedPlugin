<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Template\Context\Factory;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Template\Context\TemplateContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

interface TemplateContextFactoryInterface
{
    /**
     * @param string $class
     * @param FeedInterface $feed
     * @param ChannelInterface $channel
     * @param LocaleInterface $locale
     *
     * @return TemplateContextInterface
     */
    public function create(string $class, FeedInterface $feed, ChannelInterface $channel, LocaleInterface $locale): TemplateContextInterface;
}
