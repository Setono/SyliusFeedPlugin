<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Publish;

/**
 * The outcome of running a context's candidate through the publish gate (§6.6): whether promotion
 * to canonical storage is blocked, and the human-readable reasons every tripped guardrail recorded
 * (including non-blocking `warn` guardrails).
 */
final class PublishDecision
{
    /**
     * @param list<string> $reasons
     */
    public function __construct(
        public readonly bool $blocked,
        public readonly array $reasons = [],
    ) {
    }
}
