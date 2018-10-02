<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Entity;

use Ramsey\Uuid\Uuid;

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
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     *
     * @return Feed
     */
    public function setSlug(string $slug): FeedInterface
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Feed
     */
    public function setName(string $name): FeedInterface
    {
        $this->name = $name;

        return $this;
    }
}
