<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Publish;

use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;

/**
 * A single publish-gate check (§6.6). Given the freshly generated candidate result and, when it
 * exists, the previously published baseline for the same context, it decides whether the candidate
 * looks wrong enough that it should not silently replace the live feed.
 *
 * Collected into the {@see GuardrailRegistry} via the `setono_sylius_feed.guardrail` tag.
 */
interface GuardrailInterface
{
    /**
     * e.g. "min_items", "max_drop_pct", "non_empty", "min_bytes", "max_exclusion_pct",
     * "max_growth_pct".
     */
    public function getType(): string;

    /**
     * @param array<string, mixed> $params
     *
     * @return bool true when the guardrail is TRIPPED (the candidate looks wrong)
     */
    public function evaluate(
        FeedContextResultInterface $candidate,
        ?FeedContextResultInterface $baseline,
        array $params,
    ): bool;
}
