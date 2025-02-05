<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Component\Resource\Model\TimestampableTrait;

class FeedUpdateBatch implements FeedUpdateBatchInterface
{
    use TimestampableTrait;
    use VersionedTrait;

    protected ?int $id = null;

    protected ?FeedUpdateInterface $feedUpdate = null;

    protected string $state = self::STATE_PENDING;

    protected ?\DateTimeInterface $startedAt = null;

    protected ?\DateTimeInterface $completedAt = null;

    protected ?\DateTimeInterface $failedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFeedUpdate(): ?FeedUpdateInterface
    {
        return $this->feedUpdate;
    }

    public function setFeedUpdate(?FeedUpdateInterface $feedUpdate): void
    {
        $this->feedUpdate = $feedUpdate;
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
