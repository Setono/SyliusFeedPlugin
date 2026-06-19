<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * Whether the variant is on sale for the context channel — i.e. its original price is set and
 * higher than its current price (§8.1).
 */
final class OnSaleResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'on_sale';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.on_sale';
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
        $channel = $context->getChannel();
        if (!$entity instanceof ProductVariantInterface || !$channel instanceof ChannelInterface) {
            return false;
        }

        $channelPricing = $entity->getChannelPricingForChannel($channel);
        if (null === $channelPricing) {
            return false;
        }

        $price = $channelPricing->getPrice();
        $originalPrice = $channelPricing->getOriginalPrice();

        return null !== $price && null !== $originalPrice && $originalPrice > $price;
    }
}
