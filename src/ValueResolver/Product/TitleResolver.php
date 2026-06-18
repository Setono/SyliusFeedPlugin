<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * The translated product name for the context locale (§8.1).
 */
final class TitleResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'title';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.title';
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
        $locale = $context->getLocale();
        if (!$entity instanceof ProductVariantInterface || null === $locale) {
            return null;
        }

        $product = $entity->getProduct();
        if (null === $product) {
            return null;
        }

        return $product->getTranslation($locale)->getName();
    }
}
