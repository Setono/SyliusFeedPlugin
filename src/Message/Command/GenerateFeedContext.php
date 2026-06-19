<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

use Setono\SyliusFeedPlugin\Message\CommandInterface;

/**
 * Generates one context's feed file (to temporary storage) and, when it is the last context of the
 * run, completes the feed. The context is carried as scalars so the message is transport-safe.
 */
final class GenerateFeedContext implements CommandInterface
{
    public function __construct(
        public readonly int $feedId,
        public readonly ?string $channelCode = null,
        public readonly ?string $locale = null,
        public readonly ?string $currencyCode = null,
    ) {
    }
}
