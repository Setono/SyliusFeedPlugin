<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use Ramsey\Uuid\Uuid;
use Sylius\Component\Channel\Model\ChannelInterface as BaseChannelInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ToggleableTrait;

class Feed implements FeedInterface
{
    use ToggleableTrait;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $slug;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $template;

    /**
     * @var Collection|ChannelInterface[]
     */
    protected $channels;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->slug = Uuid::uuid4()->toString();
        $this->channels = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
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
}
