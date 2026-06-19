<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * Whether the variant's product is configurable (has more than one variant) — drives the
 * `item_group_id` emit condition (§8.1).
 */
final class IsConfigurableResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'is_configurable';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.is_configurable';
    }

    public function getType(): FieldType
    {
        return FieldType::BOOL;
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

        return $entity->getProduct()?->getVariants()->count() > 1;
    }
}
