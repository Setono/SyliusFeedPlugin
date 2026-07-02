<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Repository;

use Setono\SyliusFeedPlugin\Model\FeedChunkInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<FeedChunkInterface>
 */
interface FeedChunkRepositoryInterface extends RepositoryInterface
{
    /**
     * Atomically marks the given chunk row completed, but only if it was not already completed (a
     * conditional `UPDATE … SET completed = true WHERE … AND completed = false`). Idempotent: a
     * retried chunk that was already counted flips nothing, so the barrier never double-counts (§6.3).
     */
    public function markCompleted(FeedInterface $feed, string $contextKey, int $chunkIndex): void;

    /**
     * Whether every chunk row of the context is completed — the fan-in barrier condition (§6.3).
     * Counts distinct completed rows against the total, so it is unaffected by retries. Returns false
     * when the context has no chunk rows.
     */
    public function allCompleted(FeedInterface $feed, string $contextKey): bool;

    /**
     * The chunk rows of the context, ordered by chunk index ascending — read at finalize to sum the
     * per-chunk item/exclusion counts before the rows are consumed.
     *
     * @return list<FeedChunkInterface>
     */
    public function findForContext(FeedInterface $feed, string $contextKey): array;

    /**
     * Atomically deletes every chunk row of the context and returns how many were deleted — the
     * exactly-once finalize claim (§6.3): the finalize that deletes the rows (returns > 0) proceeds;
     * a concurrent or retried finalize finds them gone (returns 0) and is a no-op.
     */
    public function deleteForContext(FeedInterface $feed, string $contextKey): int;
}
