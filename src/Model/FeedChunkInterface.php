<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * The barrier bookkeeping row for one chunk of a fan-out generation run (§6.3): one row per
 * (feed, contextKey, chunkIndex). A chunk handler flips {@see isCompleted()} exactly once (an atomic
 * conditional update), and the finalize step fires only when every chunk row of the context is
 * completed — so out-of-order or retried chunk completion is safe and finalize runs exactly once.
 */
interface FeedChunkInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getFeed(): ?FeedInterface;

    public function setFeed(?FeedInterface $feed): void;

    public function getContextKey(): ?string;

    public function setContextKey(?string $contextKey): void;

    public function getChunkIndex(): int;

    public function setChunkIndex(int $chunkIndex): void;

    public function isCompleted(): bool;

    public function setCompleted(bool $completed): void;

    public function getItemCount(): int;

    public function setItemCount(int $itemCount): void;

    public function getExcludedCount(): int;

    public function setExcludedCount(int $excludedCount): void;

    /**
     * A bounded sample of the chunk's per-item exclusion reasons, merged into the context result at
     * finalize.
     *
     * @return list<array{item: ?string, reason: string}>
     */
    public function getErrors(): array;

    /**
     * @param list<array{item: ?string, reason: string}> $errors
     */
    public function setErrors(array $errors): void;
}
