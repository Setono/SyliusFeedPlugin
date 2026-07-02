<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Mapping;

use Setono\SyliusFeedPlugin\Model\FeedFieldInterface;
use Webmozart\Assert\Assert;

/**
 * The runtime form of a FeedField (§4.2): an output field mapped to a source, an ordered
 * transformation chain, an optional emit condition, and a `requiresInput` flag.
 *
 * A MappingPreset returns FieldMappings that seed a source's FeedFields; the generator
 * consumes FieldMappings hydrated from those FeedFields.
 */
final class FieldMapping
{
    /** @var list<TransformationConfig> */
    private array $transformations = [];

    /** @var array{field: string, operator: string, value?: mixed}|null */
    private ?array $condition = null;

    private bool $requiresInput = false;

    public function __construct(
        private readonly string $outputField,
        private readonly SourceType $sourceType,
        private readonly string $sourceValue,
    ) {
    }

    /**
     * Map an output field to a named source field / value resolver.
     */
    public static function field(string $outputField, string $sourceField): self
    {
        return new self($outputField, SourceType::FIELD, $sourceField);
    }

    /**
     * Map an output field to a static literal value.
     */
    public static function literal(string $outputField, string $value): self
    {
        return new self($outputField, SourceType::LITERAL, $value);
    }

    public static function expression(string $outputField, string $expression): self
    {
        return new self($outputField, SourceType::EXPRESSION, $expression);
    }

    public static function twig(string $outputField, string $template): self
    {
        return new self($outputField, SourceType::TWIG, $template);
    }

    /**
     * Hydrate the runtime mapping from a persisted FeedField (what the generator consumes).
     */
    public static function fromFeedField(FeedFieldInterface $field): self
    {
        $outputField = $field->getOutputField();
        Assert::notNull($outputField, 'A FeedField must have an output field to be mapped');

        $mapping = new self($outputField, SourceType::from($field->getSourceType()), $field->getSourceValue() ?? '');

        foreach ($field->getTransformations() as $transformation) {
            $mapping->transform(TransformationConfig::fromArray($transformation));
        }

        $condition = $field->getCondition();
        if (null !== $condition) {
            $mapping->condition = array_key_exists('value', $condition)
                ? ['field' => $condition['field'], 'operator' => $condition['operator'], 'value' => $condition['value']]
                : ['field' => $condition['field'], 'operator' => $condition['operator']];
        }

        return $mapping->requiresInput($field->getRequiresInput());
    }

    /**
     * Write this mapping onto a FeedField (used when a preset seeds a source's rows).
     */
    public function writeTo(FeedFieldInterface $field): void
    {
        $field->setOutputField($this->outputField);
        $field->setSourceType($this->sourceType->value);
        $field->setSourceValue($this->sourceValue);
        $field->setTransformations(array_map(
            static fn (TransformationConfig $transformation): array => $transformation->toArray(),
            $this->transformations,
        ));
        $field->setCondition($this->condition);
        $field->setRequiresInput($this->requiresInput);
    }

    public function transform(TransformationConfig $transformation): self
    {
        $this->transformations[] = $transformation;

        return $this;
    }

    /**
     * Emit this field only when the condition holds, using the shared operator vocabulary (§10):
     * the `field` and `value` are references (resolved by the reference-resolution rule) and
     * `operator` is any registered operator. Omit `$value` for operators that take no operand
     * (`empty`, `not_empty`, `true`, `false`).
     */
    public function when(string $field, string $operator, mixed $value = null): self
    {
        $this->condition = null === $value
            ? ['field' => $field, 'operator' => $operator]
            : ['field' => $field, 'operator' => $operator, 'value' => $value];

        return $this;
    }

    /**
     * Emit this field only when the named boolean source field is truthy. Shorthand for
     * {@see when()} with the `true` operator (§7, §10).
     */
    public function onlyIf(string $field): self
    {
        return $this->when($field, 'true');
    }

    public function requiresInput(bool $requiresInput = true): self
    {
        $this->requiresInput = $requiresInput;

        return $this;
    }

    public function getOutputField(): string
    {
        return $this->outputField;
    }

    public function getSourceType(): SourceType
    {
        return $this->sourceType;
    }

    public function getSourceValue(): string
    {
        return $this->sourceValue;
    }

    /**
     * @return list<TransformationConfig>
     */
    public function getTransformations(): array
    {
        return $this->transformations;
    }

    /**
     * @return array{field: string, operator: string, value?: mixed}|null
     */
    public function getCondition(): ?array
    {
        return $this->condition;
    }

    public function getRequiresInput(): bool
    {
        return $this->requiresInput;
    }
}
