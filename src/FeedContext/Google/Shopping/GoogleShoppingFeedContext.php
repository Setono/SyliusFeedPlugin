<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedContext\Google\Shopping;

use Setono\SyliusFeedPlugin\FeedContext\FeedContextInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;

final class GoogleShoppingFeedContext implements FeedContextInterface
{
    public function getContext(FeedInterface $feed, string $channel, string $locale): array
    {
        return [
            'title' => 'title',
            'url' => 'url',
            'description' => 'description',
        ];
    }
}
