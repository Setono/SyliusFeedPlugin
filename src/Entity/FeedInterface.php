<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Entity;

use Sylius\Component\Resource\Model\ResourceInterface;

interface FeedInterface extends ResourceInterface
{
    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @return string|null
     */
    public function getSlug(): ?string;

    /**
     * @param string $slug
     *
     * @return Feed
     */
    public function setSlug(string $slug): self;

    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @param string $name
     *
     * @return Feed
     */
    public function setName(string $name): self;
}
