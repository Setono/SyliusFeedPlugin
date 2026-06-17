<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;

/**
 * Extracts one named value from a source entity in a context, e.g. `channel_price`,
 * `main_image`, or a property path (§5).
 *
 * Collected into the ValueResolverRegistry via the `setono_sylius_feed.value_resolver` tag.
 */
interface ValueResolverInterface
{
    public function getName(): string;

    public function getLabel(): string;

    public function getType(): FieldType;

    /**
     * @param class-string $resourceClass
     */
    public function supports(string $resourceClass): bool;

    /**
     * @return scalar|array<array-key, mixed>|null
     */
    public function resolve(object $entity, FeedContext $context): mixed;
}
