<?php

declare(strict_types=1);

namespace Loevgaard\SyliusFeedPlugin\Entity;

use Sylius\Component\Resource\Model\ResourceInterface;

interface FeedInterface extends ResourceInterface
{
    public function getId(): int;

    /**
     * @param int $id
     * @return Feed
     */
    public function setId(int $id) : FeedInterface;

    /**
     * @return string
     */
    public function getSlug(): string;

    /**
     * @param string $slug
     * @return Feed
     */
    public function setSlug(string $slug) : FeedInterface;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     * @return Feed
     */
    public function setName(string $name) : FeedInterface;
}