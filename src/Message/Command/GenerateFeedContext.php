<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

use Setono\SyliusFeedPlugin\Message\CommandInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;

/**
 * Generates one context's feed file (to temporary storage) and, when it is the last context of the
 * run, completes the feed. The context is carried as scalars so the message is transport-safe.
 */
final class GenerateFeedContext implements CommandInterface
{
    public readonly int $feed;

    public function __construct(
        int|FeedInterface $feed,
        public readonly ?string $channelCode = null,
        public readonly ?string $locale = null,
        public readonly ?string $currencyCode = null,
    ) {
        $this->feed = $feed instanceof FeedInterface ? (int) $feed->getId() : $feed;
    }
}
