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

    /** @var class-string|null */
    protected ?string $entity = null;

    /** @var list<int>|null */
    protected ?array $ids = null;

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

    public function getEntity(): ?string
    {
        return $this->entity;
    }

    public function setEntity(?string $entity): void
    {
        $this->entity = $entity;
    }

    public function getIds(): array
    {
        return $this->ids ?? [];
    }

    public function setIds(?array $ids): void
    {
        if ([] === $ids) {
            $ids = null;
        }

        $this->ids = $ids;
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
