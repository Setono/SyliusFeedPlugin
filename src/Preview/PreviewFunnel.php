<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Preview;

/**
 * The stage-by-stage survivor counts of a dry-run preview over the sampled source items (§11).
 * Every count is monotonically non-increasing from {@see $source} down to {@see $included}, and
 * each excluded item is attributed to exactly one stage, so the drops between adjacent stages
 * explain the whole funnel.
 */
final class PreviewFunnel
{
    public function __construct(
        public readonly int $source,
        public readonly int $afterPreFilters,
        public readonly int $afterMappingValidation,
        public readonly int $afterPostFilters,
        public readonly int $included,
    ) {
    }
}
