<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Currency\ContextCurrencyConverterInterface;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * The variant's price for the context channel, in minor units, FX-converted to the context
 * currency (§9.1, §18.6).
 */
final class ChannelPriceResolver implements ValueResolverInterface
{
    public function __construct(private readonly ContextCurrencyConverterInterface $currencyConverter)
    {
    }

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

        $price = $entity->getChannelPricingForChannel($channel)?->getPrice();

        return null === $price ? null : $this->currencyConverter->convert($price, $channel, $context);
    }
}
