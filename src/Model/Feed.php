<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Ramsey\Uuid\Uuid;

class Feed implements FeedInterface
{
    use ChannelsAwareTrait {
        ChannelsAwareTrait::__construct as private __channelsTraitConstruct;
    }

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
     * @throws \Exception
     */
    public function __construct()
    {
        $this->slug = Uuid::uuid4()->toString();

        $this->__channelsTraitConstruct();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * {@inheritdoc}
     */
    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
