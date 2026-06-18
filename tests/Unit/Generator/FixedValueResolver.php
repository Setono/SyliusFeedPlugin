<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Generator;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;

/**
 * A test value resolver that returns a fixed value regardless of the entity — used to exercise the
 * generator pipeline without real Sylius entities.
 */
final class FixedValueResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly string $name,
        private readonly FieldType $type,
        private readonly mixed $value,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return 'test.' . $this->name;
    }

    public function getType(): FieldType
    {
        return $this->type;
    }

    public function supports(string $resourceClass): bool
    {
        return true;
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        return $this->value;
    }
}
