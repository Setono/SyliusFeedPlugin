<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * The variant's code — the per-item `id` for a product_variant feed (§8.1).
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
        return is_a($resourceClass, ProductVariantInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        return $entity instanceof ProductVariantInterface ? $entity->getCode() : null;
    }
}
