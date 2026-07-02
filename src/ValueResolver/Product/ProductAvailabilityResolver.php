<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\Google\Availability;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * Maps a product's inventory to an availability value (§8.7, §9.7): the product is in stock when any
 * of its variants is in stock — an untracked variant is always in stock, a tracked one is in stock
 * when its available quantity (on-hand minus on-hold) is positive.
 */
final class ProductAvailabilityResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'product_availability';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.product_availability';
    }

    public function getType(): FieldType
    {
        return FieldType::STRING;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, ProductInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        if (!$entity instanceof ProductInterface) {
            return null;
        }

        foreach ($entity->getVariants() as $variant) {
            if (!$variant instanceof ProductVariantInterface) {
                continue;
            }

            if (!$variant->isTracked()) {
                return Availability::IN_STOCK->value;
            }

            if ((int) $variant->getOnHand() - (int) $variant->getOnHold() > 0) {
                return Availability::IN_STOCK->value;
            }
        }

        return Availability::OUT_OF_STOCK->value;
    }
}
