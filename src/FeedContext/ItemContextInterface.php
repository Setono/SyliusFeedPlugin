<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedContext;

use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

interface ItemContextInterface
{
    public function getContextList(object $object, ChannelInterface $channel, LocaleInterface $locale): ContextListInterface;
}
