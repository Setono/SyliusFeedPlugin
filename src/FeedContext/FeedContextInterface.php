<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedContext;

use Setono\SyliusFeedPlugin\Model\FeedInterface;

interface FeedContextInterface
{
    public function getContext(FeedInterface $feed, string $channel, string $locale): array;
}
