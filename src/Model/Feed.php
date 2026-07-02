<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ToggleableTrait;
use Sylius\Component\Resource\Model\TranslatableTrait;

class Feed implements FeedInterface
{
    use ToggleableTrait;
    use TranslatableTrait;

    protected ?int $id = null;

    protected ?string $code = null;

    protected ?string $format = null;

    /** @var Collection<int, ChannelInterface> */
    protected Collection $channels;

    protected string $state = FeedGraph::STATE_READY;

    /** @var array<string, mixed> */
    protected array $formatConfig = [];

    /** @var array<string, mixed> */
    protected array $publishConfig = [];

    /** @var Collection<int, FeedSourceInterface> */
    protected Collection $sources;

    /** @var Collection<int, DeliveryTargetInterface> */
    protected Collection $deliveryTargets;

    protected ?\DateTimeInterface $lastGeneratedAt = null;

    protected ?int $contextCount = null;

    protected int $completedContextCount = 0;

    public function __construct()
    {
        /** @var ArrayCollection<string, FeedTranslationInterface> $translations */
        $translations = new ArrayCollection();
        $this->translations = $translations;

        /** @var ArrayCollection<int, ChannelInterface> $channels */
        $channels = new ArrayCollection();
        $this->channels = $channels;

        /** @var ArrayCollection<int, FeedSourceInterface> $sources */
        $sources = new ArrayCollection();
        $this->sources = $sources;

        /** @var ArrayCollection<int, DeliveryTargetInterface> $deliveryTargets */
        $deliveryTargets = new ArrayCollection();
        $this->deliveryTargets = $deliveryTargets;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getName(): ?string
    {
        return $this->getFeedTranslation()->getName();
    }

    public function setName(?string $name): void
    {
        $this->getFeedTranslation()->setName($name);
    }

    public function getSlug(): ?string
    {
        return $this->getFeedTranslation()->getSlug();
    }

    public function setSlug(?string $slug): void
    {
        $this->getFeedTranslation()->setSlug($slug);
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): void
    {
        $this->format = $format;
    }

    public function getChannels(): Collection
    {
        return $this->channels;
    }

    public function addChannel(ChannelInterface $channel): void
    {
        if (!$this->hasChannel($channel)) {
            $this->channels->add($channel);
        }
    }

    public function removeChannel(ChannelInterface $channel): void
    {
        if ($this->hasChannel($channel)) {
            $this->channels->removeElement($channel);
        }
    }

    public function hasChannel(ChannelInterface $channel): bool
    {
        return $this->channels->contains($channel);
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getFormatConfig(): array
    {
        return $this->formatConfig;
    }

    public function setFormatConfig(array $formatConfig): void
    {
        $this->formatConfig = $formatConfig;
    }

    public function getPublishConfig(): array
    {
        return $this->publishConfig;
    }

    public function setPublishConfig(array $publishConfig): void
    {
        $this->publishConfig = $publishConfig;
    }

    public function getSources(): Collection
    {
        return $this->sources;
    }

    public function addSource(FeedSourceInterface $source): void
    {
        if (!$this->hasSource($source)) {
            $source->setFeed($this);
            $this->sources->add($source);
        }
    }

    public function removeSource(FeedSourceInterface $source): void
    {
        if ($this->hasSource($source)) {
            $source->setFeed(null);
            $this->sources->removeElement($source);
        }
    }

    public function hasSource(FeedSourceInterface $source): bool
    {
        return $this->sources->contains($source);
    }

    public function getDeliveryTargets(): Collection
    {
        return $this->deliveryTargets;
    }

    public function addDeliveryTarget(DeliveryTargetInterface $deliveryTarget): void
    {
        if (!$this->hasDeliveryTarget($deliveryTarget)) {
            $deliveryTarget->setFeed($this);
            $this->deliveryTargets->add($deliveryTarget);
        }
    }

    public function removeDeliveryTarget(DeliveryTargetInterface $deliveryTarget): void
    {
        if ($this->hasDeliveryTarget($deliveryTarget)) {
            $deliveryTarget->setFeed(null);
            $this->deliveryTargets->removeElement($deliveryTarget);
        }
    }

    public function hasDeliveryTarget(DeliveryTargetInterface $deliveryTarget): bool
    {
        return $this->deliveryTargets->contains($deliveryTarget);
    }

    public function getLastGeneratedAt(): ?\DateTimeInterface
    {
        return $this->lastGeneratedAt;
    }

    public function setLastGeneratedAt(?\DateTimeInterface $lastGeneratedAt): void
    {
        $this->lastGeneratedAt = $lastGeneratedAt;
    }

    public function getContextCount(): ?int
    {
        return $this->contextCount;
    }

    public function setContextCount(?int $contextCount): void
    {
        $this->contextCount = $contextCount;
    }

    public function getCompletedContextCount(): int
    {
        return $this->completedContextCount;
    }

    public function setCompletedContextCount(int $completedContextCount): void
    {
        $this->completedContextCount = $completedContextCount;
    }

    protected function createTranslation(): FeedTranslationInterface
    {
        return new FeedTranslation();
    }

    private function getFeedTranslation(): FeedTranslationInterface
    {
        /** @var FeedTranslationInterface $translation */
        $translation = $this->getTranslation();

        return $translation;
    }
}
