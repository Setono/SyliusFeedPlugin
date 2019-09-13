<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Model\FeedInterface;
use SplFileInfo;

interface FeedPathGeneratorInterface
{
    /**
     * The return file can be a directory or a file
     */
    public function generate(FeedInterface $feed, string $channelCode, string $localeCode): SplFileInfo;
}
