<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * A persisted report of one context's generation run (§11): how many items were written vs
 * excluded, the size of the produced file, and a bounded sample of per-item exclusion reasons.
 * The most recent row per (feed, contextKey) feeds the publish gate in a later chunk.
 */
interface FeedContextResultInterface extends ResourceInterface
{
    /**
     * The candidate has been recorded but not yet evaluated by the publish gate (§6.6).
     */
    public const PUBLISH_STATE_PENDING = 'pending';

    /**
     * The candidate passed the publish gate and its file was promoted to canonical storage.
     */
    public const PUBLISH_STATE_PUBLISHED = 'published';

    /**
     * The candidate tripped a `block`-severity guardrail; its file is retained in staging and the
     * previously published canonical file is kept live.
     */
    public const PUBLISH_STATE_BLOCKED = 'blocked';

    public function getId(): ?int;

    public function getFeed(): ?FeedInterface;

    public function setFeed(?FeedInterface $feed): void;

    /**
     * The {@see \Setono\SyliusFeedPlugin\Context\FeedContext::key()} the run produced, e.g. "web_en_US_USD".
     */
    public function getContextKey(): ?string;

    public function setContextKey(?string $contextKey): void;

    public function getItemCount(): int;

    public function setItemCount(int $itemCount): void;

    public function getExcludedCount(): int;

    public function setExcludedCount(int $excludedCount): void;

    /**
     * The byte size of the produced feed file.
     */
    public function getBytes(): int;

    public function setBytes(int $bytes): void;

    /**
     * A bounded sample of per-item exclusion reasons collected during generation.
     *
     * @return list<array{item: ?string, reason: string}>
     */
    public function getErrors(): array;

    /**
     * @param list<array{item: ?string, reason: string}> $errors
     */
    public function setErrors(array $errors): void;

    public function addError(?string $item, string $reason): void;

    /**
     * The publish-gate outcome for this candidate (§6.6): one of the PUBLISH_STATE_* constants.
     */
    public function getPublishState(): string;

    public function setPublishState(string $publishState): void;

    /**
     * The reasons recorded by the publish gate when guardrails tripped, or null when none did.
     *
     * @return list<string>|null
     */
    public function getPublishCheck(): ?array;

    /**
     * @param list<string>|null $publishCheck
     */
    public function setPublishCheck(?array $publishCheck): void;

    public function isPublished(): bool;

    public function isBlocked(): bool;

    public function getCreatedAt(): \DateTimeInterface;

    public function setCreatedAt(\DateTimeInterface $createdAt): void;
}
