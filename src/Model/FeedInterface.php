<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Model\CodeAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\ToggleableInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;

interface FeedInterface extends ResourceInterface, CodeAwareInterface, ToggleableInterface, TranslatableInterface
{
    public function getId(): ?int;

    public function getName(): ?string;

    public function setName(?string $name): void;

    public function getSlug(): ?string;

    public function setSlug(?string $slug): void;

    public function getFormat(): ?string;

    public function setFormat(?string $format): void;

    /**
     * @return Collection<int, ChannelInterface>
     */
    public function getChannels(): Collection;

    public function addChannel(ChannelInterface $channel): void;

    public function removeChannel(ChannelInterface $channel): void;

    public function hasChannel(ChannelInterface $channel): bool;

    public function getState(): string;

    public function setState(string $state): void;

    /**
     * @return array<string, mixed>
     */
    public function getFormatConfig(): array;

    /**
     * @param array<string, mixed> $formatConfig
     */
    public function setFormatConfig(array $formatConfig): void;

    /**
     * The publish-gate configuration (§6.6): the guardrails evaluated before a freshly generated
     * context is promoted from staging to canonical storage. Shape:
     * {guardrails: list<{type: string, params: array<string, mixed>, severity: 'block'|'warn'}>}.
     *
     * @return array<string, mixed>
     */
    public function getPublishConfig(): array;

    /**
     * @param array<string, mixed> $publishConfig
     */
    public function setPublishConfig(array $publishConfig): void;

    /**
     * @return Collection<int, FeedSourceInterface>
     */
    public function getSources(): Collection;

    public function addSource(FeedSourceInterface $source): void;

    public function removeSource(FeedSourceInterface $source): void;

    public function hasSource(FeedSourceInterface $source): bool;

    /**
     * The per-context delivery targets a completed feed is pushed to (§12).
     *
     * @return Collection<int, DeliveryTargetInterface>
     */
    public function getDeliveryTargets(): Collection;

    public function addDeliveryTarget(DeliveryTargetInterface $deliveryTarget): void;

    public function removeDeliveryTarget(DeliveryTargetInterface $deliveryTarget): void;

    public function hasDeliveryTarget(DeliveryTargetInterface $deliveryTarget): bool;

    public function getLastGeneratedAt(): ?\DateTimeInterface;

    public function setLastGeneratedAt(?\DateTimeInterface $lastGeneratedAt): void;

    /**
     * The total number of contexts in the current generation run (set when processing starts).
     */
    public function getContextCount(): ?int;

    public function setContextCount(?int $contextCount): void;

    /**
     * The number of contexts generated so far in the current run; completion is reached when it
     * equals {@see getContextCount()}.
     */
    public function getCompletedContextCount(): int;

    public function setCompletedContextCount(int $completedContextCount): void;
}
