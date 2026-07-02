<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Event;

use Setono\SyliusFeedPlugin\Model\FeedInterface;

/**
 * Dispatched when the publish gate trips for a context (§6.6): either promotion was blocked, or a
 * non-blocking `warn` guardrail recorded a concern. Listeners can notify operators, log, or open a
 * task. {@see $blocked} distinguishes a hard block (the live feed was kept) from a warning (the new
 * candidate was still promoted).
 */
final class FeedPublishBlockedEvent
{
    /**
     * @param list<string> $reasons
     */
    public function __construct(
        public readonly FeedInterface $feed,
        public readonly string $contextKey,
        public readonly array $reasons,
        public readonly bool $blocked,
    ) {
    }
}
