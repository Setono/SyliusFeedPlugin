<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Publish;

use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;

interface PublishGateInterface
{
    /**
     * Evaluates the configured guardrails for one context's candidate against its baseline (§6.6).
     * An empty guardrail list means "no gate" and never blocks. The list is the raw, untrusted
     * `publishConfig['guardrails']` value; each entry is expected to be
     * `{type: string, params?: array, severity?: 'block'|'warn'}` and anything else is skipped.
     *
     * @param array<array-key, mixed> $guardrails
     */
    public function evaluate(
        FeedContextResultInterface $candidate,
        ?FeedContextResultInterface $baseline,
        array $guardrails,
    ): PublishDecision;
}
