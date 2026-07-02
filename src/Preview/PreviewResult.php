<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Preview;

/**
 * The outcome of a dry-run preview (§11): the {@see PreviewFunnel} of stage counts, a bounded
 * sample of the mapped output bags that would be included, and a bounded sample of the excluded
 * items with the reason each was dropped. Nothing is written — this is purely diagnostic.
 */
final class PreviewResult
{
    /**
     * @param list<array<string, mixed>> $included the mapped output bags of included sample items
     * @param list<array{item: ?string, reason: string}> $excluded excluded sample items + why
     */
    public function __construct(
        public readonly PreviewFunnel $funnel,
        public readonly array $included,
        public readonly array $excluded,
    ) {
    }
}
