<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;

/**
 * Whether the product is configurable (has more than one variant) — drives the `item_group_id`
 * emit condition (§8.1, §8.7).
 */
final class IsConfigurableResolver implements ValueResolverInterface
{
    use ProductAwareTrait;

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

    public function resolve(object $entity, FeedContext $context): mixed
    {
        $product = $this->resolveProduct($entity);
        if (null === $product) {
            return null;
        }

        return $product->getVariants()->count() > 1;
    }
}
