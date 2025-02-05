<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;

interface FeedUpdateInterface extends ResourceInterface, TimestampableInterface
{
    public const STATE_PENDING = 'pending';

    public const STATE_PROCESSING = 'processing';

    public const STATE_COMPLETED = 'completed';

    public const STATE_FAILED = 'failed';

    public function getId(): ?int;

    public function getFeed(): ?FeedInterface;

    public function setFeed(?FeedInterface $feed): void;

    public function getState(): string;

    public function setState(string $state): void;

    public function getStartedAt(): ?\DateTimeInterface;

    public function setStartedAt(?\DateTimeInterface $startedAt): void;

    public function getCompletedAt(): ?\DateTimeInterface;

    public function setCompletedAt(?\DateTimeInterface $completedAt): void;

    public function getFailedAt(): ?\DateTimeInterface;

    public function setFailedAt(?\DateTimeInterface $failedAt): void;
}
