<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use SplFileInfo;

interface FeedPathGeneratorInterface
{
    public function generate(FeedInterface $feed, string $channelCode, string $localeCode): SplFileInfo;
}
