<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class FeedSource implements FeedSourceInterface
{
    protected ?int $id = null;

    protected ?FeedInterface $feed = null;

    protected ?string $feedType = null;

    protected ?int $position = null;

    /** @var Collection<int, FeedFieldInterface> */
    protected Collection $fields;

    /** @var Collection<int, FeedFilterInterface> */
    protected Collection $filters;

    public function __construct()
    {
        /** @var ArrayCollection<int, FeedFieldInterface> $fields */
        $fields = new ArrayCollection();
        $this->fields = $fields;

        /** @var ArrayCollection<int, FeedFilterInterface> $filters */
        $filters = new ArrayCollection();
        $this->filters = $filters;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFeed(): ?FeedInterface
    {
        return $this->feed;
    }

    public function setFeed(?FeedInterface $feed): void
    {
        $this->feed = $feed;
    }

    public function getFeedType(): ?string
    {
        return $this->feedType;
    }

    public function setFeedType(?string $feedType): void
    {
        $this->feedType = $feedType;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function addField(FeedFieldInterface $field): void
    {
        if (!$this->hasField($field)) {
            $field->setSource($this);
            $this->fields->add($field);
        }
    }

    public function removeField(FeedFieldInterface $field): void
    {
        if ($this->hasField($field)) {
            $field->setSource(null);
            $this->fields->removeElement($field);
        }
    }

    public function hasField(FeedFieldInterface $field): bool
    {
        return $this->fields->contains($field);
    }

    public function getFilters(): Collection
    {
        return $this->filters;
    }

    public function addFilter(FeedFilterInterface $filter): void
    {
        if (!$this->hasFilter($filter)) {
            $filter->setSource($this);
            $this->filters->add($filter);
        }
    }

    public function removeFilter(FeedFilterInterface $filter): void
    {
        if ($this->hasFilter($filter)) {
            $filter->setSource(null);
            $this->filters->removeElement($filter);
        }
    }

    public function hasFilter(FeedFilterInterface $filter): bool
    {
        return $this->filters->contains($filter);
    }
}
