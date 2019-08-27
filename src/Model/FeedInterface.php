<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Component\Channel\Model\ChannelsAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\ToggleableInterface;

interface FeedInterface extends ResourceInterface, ChannelsAwareInterface, ToggleableInterface
{
    public function getId(): ?int;

    public function getUuid(): string;

    public function getState(): string;

    public function setState(string $state): void;

    public function getName(): ?string;

    public function setName(string $name): void;

    public function getFeedType(): ?string;

    public function setFeedType(string $feedType): void;

    public function getBatches(): ?int;

    public function setBatches(?int $batches): void;

    public function getFinishedBatches(): int;

    /**
     * This will reset the batches and finished batches
     * Use this method when processing starts
     */
    public function resetBatches(): void;
}
