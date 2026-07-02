<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;

/**
 * The number of variants of the product (§8.7). Works against both a variant (via its parent) and a
 * product entity.
 */
final class VariantCountResolver implements ValueResolverInterface
{
    use ProductAwareTrait;

    public function getName(): string
    {
        return 'variant_count';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.variant_count';
    }

    public function getType(): FieldType
    {
        return FieldType::INTEGER;
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        $product = $this->resolveProduct($entity);
        if (null === $product) {
            return null;
        }

        return $product->getVariants()->count();
    }
}
