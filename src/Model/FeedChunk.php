<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

class FeedChunk implements FeedChunkInterface
{
    protected ?int $id = null;

    protected ?FeedInterface $feed = null;

    protected ?string $contextKey = null;

    protected int $chunkIndex = 0;

    protected bool $completed = false;

    protected int $itemCount = 0;

    protected int $excludedCount = 0;

    /** @var list<array{item: ?string, reason: string}> */
    protected array $errors = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFeed(): ?FeedInterface
    {
        return $this->feed;
    }

    public function setFeed(?FeedInterface $feed): void
    {
        $this->feed = $feed;
    }

    public function getContextKey(): ?string
    {
        return $this->contextKey;
    }

    public function setContextKey(?string $contextKey): void
    {
        $this->contextKey = $contextKey;
    }

    public function getChunkIndex(): int
    {
        return $this->chunkIndex;
    }

    public function setChunkIndex(int $chunkIndex): void
    {
        $this->chunkIndex = $chunkIndex;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): void
    {
        $this->completed = $completed;
    }

    public function getItemCount(): int
    {
        return $this->itemCount;
    }

    public function setItemCount(int $itemCount): void
    {
        $this->itemCount = $itemCount;
    }

    public function getExcludedCount(): int
    {
        return $this->excludedCount;
    }

    public function setExcludedCount(int $excludedCount): void
    {
        $this->excludedCount = $excludedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }
}
