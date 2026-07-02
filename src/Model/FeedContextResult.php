<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

class FeedContextResult implements FeedContextResultInterface
{
    protected ?int $id = null;

    protected ?FeedInterface $feed = null;

    protected ?string $contextKey = null;

    protected int $itemCount = 0;

    protected int $excludedCount = 0;

    protected int $bytes = 0;

    /** @var list<array{item: ?string, reason: string}> */
    protected array $errors = [];

    protected \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function getBytes(): int
    {
        return $this->bytes;
    }

    public function setBytes(int $bytes): void
    {
        $this->bytes = $bytes;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    public function addError(?string $item, string $reason): void
    {
        $this->errors[] = ['item' => $item, 'reason' => $reason];
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
