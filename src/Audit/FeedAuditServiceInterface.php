<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Audit;

use Setono\SyliusFeedPlugin\Preview\PreviewResult;

/**
 * Computes an advisory {@see AuditReport} over a {@see PreviewResult}'s included items (§11). It
 * never re-runs the pipeline — it reads the already-mapped output bags the preview produced.
 */
interface FeedAuditServiceInterface
{
    public function audit(PreviewResult $result): AuditReport;
}
