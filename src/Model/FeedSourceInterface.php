<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Resource\Model\ResourceInterface;

interface FeedSourceInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getFeed(): ?FeedInterface;

    public function setFeed(?FeedInterface $feed): void;

    public function getFeedType(): ?string;

    public function setFeedType(?string $feedType): void;

    public function getPosition(): ?int;

    public function setPosition(?int $position): void;

    /**
     * @return Collection<int, FeedFieldInterface>
     */
    public function getFields(): Collection;

    public function addField(FeedFieldInterface $field): void;

    public function removeField(FeedFieldInterface $field): void;

    public function hasField(FeedFieldInterface $field): bool;

    /**
     * @return Collection<int, FeedFilterInterface>
     */
    public function getFilters(): Collection;

    public function addFilter(FeedFilterInterface $filter): void;

    public function removeFilter(FeedFilterInterface $filter): void;

    public function hasFilter(FeedFilterInterface $filter): bool;
}
