<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

/**
 * Accumulates per-item exclusions during generation (§11): every exclusion is counted, but only the
 * first {@see self::MAX_RECORDED} reasons are kept, so a feed with many excluded items cannot
 * exhaust memory. The full count and the bounded sample feed the persisted FeedContextResult.
 */
final class ExclusionCollector
{
    private const MAX_RECORDED = 1000;

    private int $count = 0;

    /** @var list<array{item: ?string, reason: string}> */
    private array $errors = [];

    public function record(?string $item, string $reason): void
    {
        ++$this->count;

        if (count($this->errors) < self::MAX_RECORDED) {
            $this->errors[] = ['item' => $item, 'reason' => $reason];
        }
    }

    public function count(): int
    {
        return $this->count;
    }

    /**
     * @return list<array{item: ?string, reason: string}>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
