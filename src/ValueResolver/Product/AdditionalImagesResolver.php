<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;

/**
 * The product's additional images (all but the first) as absolute, filtered URLs, capped at 10
 * per Google's limit (§8.1, §8.7).
 */
final class AdditionalImagesResolver implements ValueResolverInterface
{
    use ProductAwareTrait;

    private const LIMIT = 10;

    public function __construct(
        private readonly CacheManager $cacheManager,
        private readonly string $filterSet = 'sylius_shop_product_large_thumbnail',
    ) {
    }

    public function getName(): string
    {
        return 'additional_images';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.additional_images';
    }

    public function getType(): FieldType
    {
        return FieldType::IMAGE;
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        $product = $this->resolveProduct($entity);
        if (null === $product) {
            return [];
        }

        $urls = [];
        foreach ($product->getImages()->slice(1, self::LIMIT) as $image) {
            if (null !== $image->getPath()) {
                $urls[] = $this->cacheManager->getBrowserPath($image->getPath(), $this->filterSet);
            }
        }

        return $urls;
    }
}
