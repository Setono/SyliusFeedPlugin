<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Template\Context;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

interface TemplateContextInterface
{
    public function setFeed(FeedInterface $feed): void;

    public function setChannel(ChannelInterface $channel): void;

    public function setLocale(LocaleInterface $locale): void;

    /**
     * This is the array that will be given to Twig_Environment::display method
     *
     * @return array
     */
    public function asArray(): array;
}
