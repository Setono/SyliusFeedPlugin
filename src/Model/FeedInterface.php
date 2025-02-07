<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Setono\SyliusFeedPlugin\Specification\Specification;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\SlugAwareInterface;
use Sylius\Resource\Model\ToggleableInterface;

interface FeedInterface extends ResourceInterface, ToggleableInterface, SlugAwareInterface
{
    public function getId(): ?int;

    public function getName(): ?string;

    public function setName(string $name): void;

    public function getSlug(): ?string;

    public function setSlug(?string $slug): void;

    /**
     * @return list<class-string>
     */
    public function getEntities(): array;

    /**
     * @param list<class-string>|null $entities
     */
    public function setEntities(?array $entities): void;

    /**
     * @return class-string<Specification>|null
     */
    public function getSpecification(): ?string;

    /**
     * @param class-string<Specification>|null $specification
     */
    public function setSpecification(?string $specification): void;

    public function getConfiguration(): array;

    public function setConfiguration(?array $configuration): void;
}
