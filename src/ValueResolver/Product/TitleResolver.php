<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;

/**
 * The translated product name for the context locale (§8.1, §8.7).
 */
final class TitleResolver implements ValueResolverInterface
{
    use ProductAwareTrait;

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

    public function resolve(object $entity, FeedContext $context): mixed
    {
        $locale = $context->getLocale();
        if (null === $locale) {
            return null;
        }

        $product = $this->resolveProduct($entity);
        if (null === $product) {
            return null;
        }

        return $product->getTranslation($locale)->getName();
    }
}
