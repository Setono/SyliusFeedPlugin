<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * Shared behaviour for value resolvers that expose a product-level field and therefore work against
 * both a product variant and a product (§8.1, §8.7): they support either entity and derive the
 * backing {@see ProductInterface} the same way.
 */
trait ProductAwareTrait
{
    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, ProductVariantInterface::class, true) ||
            is_a($resourceClass, ProductInterface::class, true);
    }

    /**
     * Derives the product from either a variant (via its parent) or a product entity, or null for
     * anything else.
     */
    private function resolveProduct(object $entity): ?ProductInterface
    {
        if ($entity instanceof ProductVariantInterface) {
            $product = $entity->getProduct();

            return $product instanceof ProductInterface ? $product : null;
        }

        if ($entity instanceof ProductInterface) {
            return $entity;
        }

        return null;
    }
}
