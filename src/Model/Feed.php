<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Setono\SyliusFeedPlugin\Specification\Specification;
use Sylius\Component\Resource\Model\ToggleableTrait;
use Symfony\Component\Uid\Uuid;

class Feed implements FeedInterface
{
    use ToggleableTrait;

    protected ?int $id = null;

    protected ?string $name = null;

    protected ?string $slug = null;

    /** @var list<class-string>|null */
    protected ?array $entities = null;

    /** @var class-string<Specification>|null */
    protected ?string $specification = null;

    protected ?array $configuration = null;

    /** @var Collection<array-key, FeedScopeInterface> */
    protected Collection $scopes;

    public function __construct()
    {
        $this->slug = (string) Uuid::v4();
        $this->scopes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }

    public function getEntities(): array
    {
        return $this->entities ?? [];
    }

    public function setEntities(?array $entities): void
    {
        if ([] === $entities) {
            $entities = null;
        }

        $this->entities = $entities;
    }

    public function getSpecification(): ?string
    {
        return $this->specification;
    }

    public function setSpecification(?string $specification): void
    {
        $this->specification = $specification;
    }

    public function getConfiguration(): array
    {
        return $this->configuration ?? [];
    }

    public function setConfiguration(?array $configuration): void
    {
        if ([] === $configuration) {
            $configuration = null;
        }

        $this->configuration = $configuration;
    }

    public function getScopes(): Collection
    {
        return $this->scopes;
    }

    public function addScope(FeedScopeInterface $scope): void
    {
        if ($this->scopes->contains($scope)) {
            return;
        }

        $this->scopes->add($scope);
    }

    public function removeScope(FeedScopeInterface $scope): void
    {
        if (!$this->scopes->contains($scope)) {
            return;
        }

        $this->scopes->removeElement($scope);
    }
}
