<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

class DeliveryTarget implements DeliveryTargetInterface
{
    protected ?int $id = null;

    protected ?FeedInterface $feed = null;

    protected ?int $position = null;

    protected ?string $transport = null;

    /** @var array<string, mixed> */
    protected array $transportConfig = [];

    protected ?string $pathTemplate = null;

    /** @var array<string, mixed> */
    protected array $match = [];

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

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    public function getTransport(): ?string
    {
        return $this->transport;
    }

    public function setTransport(?string $transport): void
    {
        $this->transport = $transport;
    }

    public function getTransportConfig(): array
    {
        return $this->transportConfig;
    }

    public function setTransportConfig(array $transportConfig): void
    {
        $this->transportConfig = $transportConfig;
    }

    public function getPathTemplate(): ?string
    {
        return $this->pathTemplate;
    }

    public function setPathTemplate(?string $pathTemplate): void
    {
        $this->pathTemplate = $pathTemplate;
    }

    public function getMatch(): array
    {
        return $this->match;
    }

    public function setMatch(array $match): void
    {
        $this->match = $match;
    }
}
