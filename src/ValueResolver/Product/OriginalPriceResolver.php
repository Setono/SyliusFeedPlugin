<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * The variant's original (pre-discount) price for the context channel, in minor units (§9.1).
 */
final class OriginalPriceResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'original_price';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.original_price';
    }

    public function getType(): FieldType
    {
        return FieldType::MONEY;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, ProductVariantInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        $channel = $context->getChannel();
        if (!$entity instanceof ProductVariantInterface || !$channel instanceof ChannelInterface) {
            return null;
        }

        return $entity->getChannelPricingForChannel($channel)?->getOriginalPrice();
    }
}
