<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Exception;
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
     * @var string
     */
    protected $template;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->slug = Uuid::uuid4()->toString();

        $this->__channelsTraitConstruct();
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

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }
}
