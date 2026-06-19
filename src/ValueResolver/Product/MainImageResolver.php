<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\ImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * The product's main image as an absolute, filtered (LiipImagine) URL (§8.1, §9.6).
 */
final class MainImageResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly CacheManager $cacheManager,
        private readonly string $filterSet = 'sylius_shop_product_large_thumbnail',
    ) {
    }

    public function getName(): string
    {
        return 'main_image';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.main_image';
    }

    public function getType(): FieldType
    {
        return FieldType::IMAGE;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, ProductVariantInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        if (!$entity instanceof ProductVariantInterface) {
            return null;
        }

        $product = $entity->getProduct();
        if (!$product instanceof ProductInterface) {
            return null;
        }

        $image = $product->getImages()->first();
        if (!$image instanceof ImageInterface || null === $image->getPath()) {
            return null;
        }

        return $this->cacheManager->getBrowserPath($image->getPath(), $this->filterSet);
    }
}
