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

    public function getCreatedAt(): \DateTimeInterface;

    public function setCreatedAt(\DateTimeInterface $createdAt): void;
}
