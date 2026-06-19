<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

use Setono\SyliusFeedPlugin\Message\CommandInterface;

/**
 * Starts generating a feed: applies the `process` transition and fans out one
 * {@see GenerateFeedContext} per context (§6.2, §6.3).
 */
final class ProcessFeed implements CommandInterface
{
    public function __construct(public readonly int $feedId)
    {
    }
}
