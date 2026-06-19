<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * The variant's price for the context channel, in minor units of the channel's base currency
 * (§9.1). M1 emits the base-currency amount; FX conversion to the context currency lands in M2.
 */
final class ChannelPriceResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'channel_price';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.channel_price';
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

        return $entity->getChannelPricingForChannel($channel)?->getPrice();
    }
}
