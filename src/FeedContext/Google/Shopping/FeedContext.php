<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedContext\Google\Shopping;

use Setono\SyliusFeedPlugin\FeedContext\FeedContextInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

final class FeedContext implements FeedContextInterface
{
    public function getContext(FeedInterface $feed, ChannelInterface $channel, LocaleInterface $locale): array
    {
        return [
            'title' => 'title',
            'url' => 'url',
            'description' => 'description',
        ];
    }
}
