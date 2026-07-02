<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Publish;

use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;

/**
 * Tripped when the candidate's exclusion ratio — `excludedCount / (itemCount + excludedCount)` —
 * exceeds `params['pct']` percent (§6.6). The absolute threshold always applies; when a baseline is
 * present the ratio must additionally have jumped beyond the baseline's ratio, so a feed that has
 * always excluded a large (but stable) share of items does not trip on every run.
 */
final class MaxExclusionPctGuardrail extends AbstractGuardrail
{
    public function getType(): string
    {
        return 'max_exclusion_pct';
    }

    public function evaluate(
        FeedContextResultInterface $candidate,
        ?FeedContextResultInterface $baseline,
        array $params,
    ): bool {
        $candidateRatio = $this->ratio($candidate);
        if ($candidateRatio <= $this->floatParam($params, 'pct')) {
            return false;
        }

        if (null !== $baseline && $candidateRatio <= $this->ratio($baseline)) {
            return false;
        }

        return true;
    }

    /**
     * The excluded share of the run as a 0-100 percentage; 0 when nothing was processed.
     */
    private function ratio(FeedContextResultInterface $result): float
    {
        $total = $result->getItemCount() + $result->getExcludedCount();
        if ($total <= 0) {
            return 0.0;
        }

        return $result->getExcludedCount() / $total * 100;
    }
}
