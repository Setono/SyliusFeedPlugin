<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Mapping;

use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;

/**
 * Describes one available source field a feed type exposes for mapping (§4.2). Returned,
 * keyed by name, from FeedType::getAvailableFields() to populate the mapping UI's source picker.
 */
final class FieldDefinition
{
    public function __construct(
        private readonly string $name,
        private readonly string $label,
        private readonly FieldType $type,
        private readonly ValueResolverInterface $resolver,
        private readonly bool $multiple = false,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): FieldType
    {
        return $this->type;
    }

    public function getResolver(): ValueResolverInterface
    {
        return $this->resolver;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }
}
