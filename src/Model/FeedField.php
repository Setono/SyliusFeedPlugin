<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Model;

use Setono\SyliusFeedPlugin\Mapping\SourceType;

class FeedField implements FeedFieldInterface
{
    protected ?int $id = null;

    protected ?FeedSourceInterface $source = null;

    protected ?string $outputField = null;

    protected ?int $position = null;

    protected string $sourceType;

    protected ?string $sourceValue = null;

    /** @var list<array{type: string, params?: array<string, mixed>}> */
    protected array $transformations = [];

    /** @var array{field: string, operator: string, value?: mixed}|null */
    protected ?array $condition = null;

    protected bool $requiresInput = false;

    public function __construct()
    {
        $this->sourceType = SourceType::FIELD->value;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSource(): ?FeedSourceInterface
    {
        return $this->source;
    }

    public function setSource(?FeedSourceInterface $source): void
    {
        $this->source = $source;
    }

    public function getOutputField(): ?string
    {
        return $this->outputField;
    }

    public function setOutputField(?string $outputField): void
    {
        $this->outputField = $outputField;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function setSourceType(string $sourceType): void
    {
        $this->sourceType = $sourceType;
    }

    public function getSourceValue(): ?string
    {
        return $this->sourceValue;
    }

    public function setSourceValue(?string $sourceValue): void
    {
        $this->sourceValue = $sourceValue;
    }

    public function getTransformations(): array
    {
        return $this->transformations;
    }

    public function setTransformations(array $transformations): void
    {
        $this->transformations = $transformations;
    }

    public function getCondition(): ?array
    {
        return $this->condition;
    }

    public function setCondition(?array $condition): void
    {
        $this->condition = $condition;
    }

    public function getRequiresInput(): bool
    {
        return $this->requiresInput;
    }

    public function setRequiresInput(bool $requiresInput): void
    {
        $this->requiresInput = $requiresInput;
    }
}
