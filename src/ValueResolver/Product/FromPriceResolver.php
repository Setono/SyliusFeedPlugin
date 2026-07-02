<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Currency\ContextCurrencyConverterInterface;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * The cheapest enabled variant's price for the context channel, in minor units, FX-converted to the
 * context currency (§8.7, §9.1). The "from" price shown on a product-level row of a configurable
 * product.
 */
final class FromPriceResolver implements ValueResolverInterface
{
    public function __construct(private readonly ContextCurrencyConverterInterface $currencyConverter)
    {
    }

    public function getName(): string
    {
        return 'from_price';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.from_price';
    }

    public function getType(): FieldType
    {
        return FieldType::MONEY;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, ProductInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        $channel = $context->getChannel();
        if (!$entity instanceof ProductInterface || !$channel instanceof ChannelInterface) {
            return null;
        }

        $lowest = null;
        foreach ($entity->getVariants() as $variant) {
            if (!$variant instanceof ProductVariantInterface || !$variant->isEnabled()) {
                continue;
            }

            $price = $variant->getChannelPricingForChannel($channel)?->getPrice();
            if (null !== $price && (null === $lowest || $price < $lowest)) {
                $lowest = $price;
            }
        }

        return null === $lowest ? null : $this->currencyConverter->convert($lowest, $channel, $context);
    }
}
