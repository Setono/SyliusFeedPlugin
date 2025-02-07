<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TimestampableInterface;
use Sylius\Resource\Model\VersionedInterface;

interface FeedUpdateBatchInterface extends ResourceInterface, TimestampableInterface, VersionedInterface
{
    public const STATE_PENDING = 'pending';

    public const STATE_PROCESSING = 'processing';

    public const STATE_COMPLETED = 'completed';

    public const STATE_FAILED = 'failed';

    public function getId(): ?int;

    public function getFeedUpdate(): ?FeedUpdateInterface;

    public function setFeedUpdate(?FeedUpdateInterface $feedUpdate): void;

    /**
     * @return class-string|null
     */
    public function getEntity(): ?string;

    /**
     * @param class-string|null $entity
     */
    public function setEntity(?string $entity): void;

    /**
     * @return list<int>
     */
    public function getIds(): array;

    /**
     * @param list<int> $ids
     */
    public function setIds(array $ids): void;

    public function getState(): string;

    public function setState(string $state): void;

    public function setPath(?string $path): void;

    public function getPath(): ?string;

    public function getStartedAt(): ?\DateTimeInterface;

    public function setStartedAt(?\DateTimeInterface $startedAt): void;

    public function getCompletedAt(): ?\DateTimeInterface;

    public function setCompletedAt(?\DateTimeInterface $completedAt): void;

    public function getFailedAt(): ?\DateTimeInterface;

    public function setFailedAt(?\DateTimeInterface $failedAt): void;

    public function setError(?string $error): void;

    public function getError(): ?string;
}
