<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Publish;

use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;

/**
 * Tripped when the candidate's item count fell more than `params['pct']` percent below the baseline
 * (§6.6) — the classic "50k → 5k" collapse (a 90% drop). Without a baseline there is nothing to
 * compare against, so it never trips.
 */
final class MaxDropPctGuardrail extends AbstractGuardrail
{
    public function getType(): string
    {
        return 'max_drop_pct';
    }

    public function evaluate(
        FeedContextResultInterface $candidate,
        ?FeedContextResultInterface $baseline,
        array $params,
    ): bool {
        if (null === $baseline) {
            return false;
        }

        $baselineCount = $baseline->getItemCount();
        if ($baselineCount <= 0) {
            return false;
        }

        $candidateCount = $candidate->getItemCount();
        if ($candidateCount >= $baselineCount) {
            return false;
        }

        $dropPct = ($baselineCount - $candidateCount) / $baselineCount * 100;

        return $dropPct > $this->floatParam($params, 'pct');
    }
}
