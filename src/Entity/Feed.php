<?php

declare(strict_types=1);

namespace Loevgaard\SyliusFeedPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\Uuid;
use Sylius\Component\Channel\Model\ChannelInterface as BaseChannelInterface;
use Sylius\Component\Core\Model\ChannelInterface;

class Feed implements FeedInterface
{
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

    public function __construct()
    {
        $this->slug = Uuid::uuid4()->toString();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return intval($this->id);
    }

    /**
     * @param int $id
     * @return Feed
     */
    public function setId(int $id) : FeedInterface
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return (string)$this->slug;
    }

    /**
     * @param string $slug
     * @return Feed
     */
    public function setSlug(string $slug) : FeedInterface
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return (string)$this->name;
    }

    /**
     * @param string $name
     * @return Feed
     */
    public function setName(string $name) : FeedInterface
    {
        $this->name = $name;
        return $this;
    }
}