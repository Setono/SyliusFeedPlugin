<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Publish;

use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;

/**
 * Tripped when the candidate's item count grew more than `params['pct']` percent above the baseline
 * (§6.6) — a sudden, implausible expansion (e.g. a broken filter that stopped excluding). Without a
 * baseline there is nothing to compare against, so it never trips.
 */
final class MaxGrowthPctGuardrail extends AbstractGuardrail
{
    public function getType(): string
    {
        return 'max_growth_pct';
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
        if ($candidateCount <= $baselineCount) {
            return false;
        }

        $growthPct = ($candidateCount - $baselineCount) / $baselineCount * 100;

        return $growthPct > $this->floatParam($params, 'pct');
    }
}
