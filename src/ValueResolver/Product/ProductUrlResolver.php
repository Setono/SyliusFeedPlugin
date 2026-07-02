<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * The absolute product page URL for the context locale (§8.1, §8.7, §9.3). The router context host
 * is set per channel by the generator so the URL is channel-correct.
 */
final class ProductUrlResolver implements ValueResolverInterface
{
    use ProductAwareTrait;

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function getName(): string
    {
        return 'link';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.link';
    }

    public function getType(): FieldType
    {
        return FieldType::URL;
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

        $slug = $product->getTranslation($locale)->getSlug();
        if (null === $slug) {
            return null;
        }

        return $this->urlGenerator->generate(
            'sylius_shop_product_show',
            ['slug' => $slug, '_locale' => $locale],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }
}
