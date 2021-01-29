<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\Uuid;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Channel\Model\ChannelInterface as BaseChannelInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ToggleableTrait;

class Feed implements FeedInterface
{
    use ToggleableTrait;

    protected int $id;

    protected string $code;

    protected string $state = FeedGraph::STATE_UNPROCESSED;

    protected string $name;

    protected string $feedType;

    protected int $batches = 0;

    protected int $finishedBatches = 0;

    /** @var Collection|ChannelInterface[] */
    protected $channels;

    /** @var Collection|ViolationInterface[] */
    protected $violations;

    public function __construct()
    {
        $this->code = Uuid::uuid4()->toString();
        $this->channels = new ArrayCollection();
        $this->violations = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = (string) $code;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function isErrored(): bool
    {
        return FeedGraph::STATE_ERROR === $this->state;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getFeedType(): ?string
    {
        return $this->feedType;
    }

    public function setFeedType(string $feedType): void
    {
        $this->feedType = $feedType;
    }

    public function getBatches(): int
    {
        return $this->batches;
    }

    public function setBatches(int $batches): void
    {
        $this->batches = $batches;
    }

    public function getFinishedBatches(): int
    {
        return $this->finishedBatches;
    }

    public function resetBatches(): void
    {
        $this->batches = 0;
        $this->finishedBatches = 0;
    }

    public function getChannels(): Collection
    {
        return $this->channels;
    }

    public function addChannel(BaseChannelInterface $channel): void
    {
        if (!$this->hasChannel($channel)) {
            $this->channels->add($channel);
        }
    }

    public function removeChannel(BaseChannelInterface $channel): void
    {
        if ($this->hasChannel($channel)) {
            $this->channels->removeElement($channel);
        }
    }

    public function hasChannel(BaseChannelInterface $channel): bool
    {
        return $this->channels->contains($channel);
    }

    public function getViolations(): Collection
    {
        return $this->violations;
    }

    public function addViolation(ViolationInterface $violation): void
    {
        if (!$this->hasViolation($violation)) {
            $violation->setFeed($this);
            $this->violations->add($violation);
        }
    }

    public function removeViolation(ViolationInterface $violation): void
    {
        if ($this->hasViolation($violation)) {
            $violation->setFeed(null);
            $this->violations->removeElement($violation);
        }
    }

    public function hasViolation(ViolationInterface $violation): bool
    {
        return $this->violations->contains($violation);
    }

    public function clearViolations(): void
    {
        foreach ($this->violations as $violation) {
            $violation->setFeed(null);
        }

        $this->violations->clear();
    }
}
