<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Publish;

use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;

/**
 * Tripped when the candidate contains fewer than `params['min']` items — an absolute floor that is
 * independent of any baseline (§6.6).
 */
final class MinItemsGuardrail extends AbstractGuardrail
{
    public function getType(): string
    {
        return 'min_items';
    }

    public function evaluate(
        FeedContextResultInterface $candidate,
        ?FeedContextResultInterface $baseline,
        array $params,
    ): bool {
        return $candidate->getItemCount() < $this->intParam($params, 'min');
    }
}
