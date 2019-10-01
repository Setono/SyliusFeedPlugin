<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use Ramsey\Uuid\Uuid;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Channel\Model\ChannelInterface as BaseChannelInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ToggleableTrait;

class Feed implements FeedInterface
{
    use ToggleableTrait;

    /** @var int */
    protected $id;

    /** @var string */
    protected $uuid;

    /** @var string */
    protected $state = FeedGraph::STATE_UNPROCESSED;

    /** @var string */
    protected $name;

    /** @var string */
    protected $feedType;

    /** @var int */
    protected $batches = 0;

    /** @var int */
    protected $finishedBatches = 0;

    /** @var Collection|ChannelInterface[] */
    protected $channels;

    /** @var Collection|ViolationInterface[] */
    protected $violations;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->uuid = Uuid::uuid4()->toString();
        $this->channels = new ArrayCollection();
        $this->violations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
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
