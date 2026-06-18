<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * The parent product's code — the `item_group_id` that ties a configurable product's variants
 * together (§8.1). Whether to emit it (only for multi-variant products) is a mapping condition,
 * not the resolver's concern.
 */
final class ItemGroupIdResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'item_group_id';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.item_group_id';
    }

    public function getType(): FieldType
    {
        return FieldType::STRING;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, ProductVariantInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        if (!$entity instanceof ProductVariantInterface) {
            return null;
        }

        return $entity->getProduct()?->getCode();
    }
}
