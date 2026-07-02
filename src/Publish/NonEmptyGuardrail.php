<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Publish;

use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;

/**
 * Tripped when the candidate contains no items at all (§6.6). A degenerate but common failure mode
 * worth a dedicated guardrail so it can be blocked without configuring a minimum.
 */
final class NonEmptyGuardrail implements GuardrailInterface
{
    public function getType(): string
    {
        return 'non_empty';
    }

    public function evaluate(
        FeedContextResultInterface $candidate,
        ?FeedContextResultInterface $baseline,
        array $params,
    ): bool {
        return 0 === $candidate->getItemCount();
    }
}
