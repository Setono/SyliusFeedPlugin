<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Publish;

use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;

/**
 * Tripped when the candidate file is smaller than `params['bytes']` (§6.6) — catches truncated or
 * near-empty output even when the item count looks plausible.
 */
final class MinBytesGuardrail extends AbstractGuardrail
{
    public function getType(): string
    {
        return 'min_bytes';
    }

    public function evaluate(
        FeedContextResultInterface $candidate,
        ?FeedContextResultInterface $baseline,
        array $params,
    ): bool {
        return $candidate->getBytes() < $this->intParam($params, 'bytes');
    }
}
