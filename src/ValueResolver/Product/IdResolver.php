<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * The per-item `id` (§8.1, §8.7): a variant resolves to the variant's own code, a product resolves
 * to the product's code.
 */
final class IdResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'id';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.id';
    }

    public function getType(): FieldType
    {
        return FieldType::STRING;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, ProductVariantInterface::class, true) ||
            is_a($resourceClass, ProductInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        if ($entity instanceof ProductVariantInterface) {
            return $entity->getCode();
        }

        if ($entity instanceof ProductInterface) {
            return $entity->getCode();
        }

        return null;
    }
}
