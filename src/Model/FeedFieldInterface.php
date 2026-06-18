<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

interface FeedFieldInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getSource(): ?FeedSourceInterface;

    public function setSource(?FeedSourceInterface $source): void;

    public function getOutputField(): ?string;

    public function setOutputField(?string $outputField): void;

    public function getPosition(): ?int;

    public function setPosition(?int $position): void;

    public function getSourceType(): string;

    public function setSourceType(string $sourceType): void;

    public function getSourceValue(): ?string;

    public function setSourceValue(?string $sourceValue): void;

    /**
     * @return list<array{type: string, params?: array<string, mixed>}>
     */
    public function getTransformations(): array;

    /**
     * @param list<array{type: string, params?: array<string, mixed>}> $transformations
     */
    public function setTransformations(array $transformations): void;

    /**
     * @return array{field: string, operator: string, value?: mixed}|null
     */
    public function getCondition(): ?array;

    /**
     * @param array{field: string, operator: string, value?: mixed}|null $condition
     */
    public function setCondition(?array $condition): void;

    public function getRequiresInput(): bool;

    public function setRequiresInput(bool $requiresInput): void;
}
