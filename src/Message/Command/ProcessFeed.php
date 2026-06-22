<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

use Setono\SyliusFeedPlugin\Message\CommandInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;

/**
 * Starts generating a feed: applies the `process` transition and fans out one
 * {@see GenerateFeedContext} per context (§6.2, §6.3).
 */
final class ProcessFeed implements CommandInterface
{
    public readonly int $feed;

    public function __construct(int|FeedInterface $feed)
    {
        $this->feed = $feed instanceof FeedInterface ? (int) $feed->getId() : $feed;
    }
}
