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
    use TranslatableTrait {
        __construct as private initializeTranslationsCollection;
    }

    protected ?int $id = null;

    protected ?string $code = null;

    protected ?string $format = null;

    /** @var Collection<int, ChannelInterface> */
    protected Collection $channels;

    protected string $state = FeedGraph::STATE_READY;

    /** @var array<string, mixed> */
    protected array $formatConfig = [];

    /** @var Collection<int, FeedSourceInterface> */
    protected Collection $sources;

    protected ?\DateTimeInterface $lastGeneratedAt = null;

    public function __construct()
    {
        $this->initializeTranslationsCollection();

        /** @var ArrayCollection<int, ChannelInterface> $channels */
        $channels = new ArrayCollection();
        $this->channels = $channels;

        /** @var ArrayCollection<int, FeedSourceInterface> $sources */
        $sources = new ArrayCollection();
        $this->sources = $sources;
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

    public function getLastGeneratedAt(): ?\DateTimeInterface
    {
        return $this->lastGeneratedAt;
    }

    public function setLastGeneratedAt(?\DateTimeInterface $lastGeneratedAt): void
    {
        $this->lastGeneratedAt = $lastGeneratedAt;
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
