<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Component\Resource\Model\TimestampableTrait;

class FeedUpdate implements FeedUpdateInterface
{
    use TimestampableTrait;

    protected ?int $id = null;

    protected ?FeedInterface $feed = null;

    protected string $state = self::STATE_PENDING;

    protected ?\DateTimeInterface $startedAt = null;

    protected ?\DateTimeInterface $completedAt = null;

    protected ?\DateTimeInterface $failedAt = null;

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

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function getFailedAt(): ?\DateTimeInterface
    {
        return $this->failedAt;
    }

    public function setFailedAt(?\DateTimeInterface $failedAt): void
    {
        $this->failedAt = $failedAt;
    }
}
