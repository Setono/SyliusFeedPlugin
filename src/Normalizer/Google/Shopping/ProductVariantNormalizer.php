<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Normalizer\Google\Shopping;

use InvalidArgumentException;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping\Product;
use Setono\SyliusFeedPlugin\Model\BrandAwareInterface;
use Setono\SyliusFeedPlugin\Model\ConditionAwareInterface;
use Setono\SyliusFeedPlugin\Model\GtinAwareInterface;
use Setono\SyliusFeedPlugin\Normalizer\NormalizerInterface;
use Sylius\Component\Core\Model\ImagesAwareInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ProductVariantNormalizer implements NormalizerInterface
{
    /** @var AvailabilityCheckerInterface */
    private $availabilityChecker;

    /** @var CacheManager */
    private $cacheManager;

    /** @var RouterInterface */
    private $router;

    public function __construct(AvailabilityCheckerInterface $availabilityChecker, CacheManager $cacheManager, RouterInterface $router)
    {
        $this->availabilityChecker = $availabilityChecker;
        $this->cacheManager = $cacheManager;
        $this->router = $router;
    }

    /**
     * @throws StringsException
     */
    public function normalize(object $variant, string $channel, string $locale): array
    {
        if (!$variant instanceof ProductVariantInterface) {
            throw new InvalidArgumentException(sprintf('The class %s is not an instance of %s', get_class($variant), ProductVariantInterface::class));
        }

        /** @var ProductInterface|null $product */
        $product = $variant->getProduct();

        if (null === $product) {
            throw new InvalidArgumentException(sprintf('The variant "%s" does not have a product associated with it', $variant->getCode()));
        }

        $translation = $variant->getTranslation($locale);
        $productTranslation = $product->getTranslation($locale);

        $data = new Product();
        $data->id = $variant->getCode();
        $data->title = $translation->getName();
        $data->description = $productTranslation->getDescription();
        $data->link = $this->router->generate(
            'sylius_shop_product_show',
            ['slug' => $productTranslation->getSlug(), '_locale' => $locale],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $data->availability = $this->getAvailability($variant);
        //$data->price = ''; todo
        $data->condition = $variant instanceof ConditionAwareInterface ? (string) $variant->getCondition() : 'new';
        $data->itemGroupId = $product->getCode();

        $imageUrl = $this->getImageUrl($product);
        if (null !== $imageUrl) {
            $data->imageLink = $imageUrl;
        }

        if ($variant instanceof BrandAwareInterface) {
            $data->brand = (string) $variant->getBrand();
        } elseif ($product instanceof BrandAwareInterface) {
            $data->brand = (string) $product->getBrand();
        }

        if ($variant instanceof GtinAwareInterface) {
            $data->gtin = (string) $variant->getGtin();
        }

        return [$data];
    }

    private function getAvailability(ProductVariantInterface $product): string
    {
        return $this->availabilityChecker->isStockAvailable($product) ? 'in stock' : 'out of stock';
    }

    private function getImageUrl(ImagesAwareInterface $imagesAware): ?string
    {
        $images = $imagesAware->getImagesByType('main');
        if ($images->count() === 0) {
            $images = $imagesAware->getImages();
        }

        if ($images->count() === 0) {
            return null;
        }

        return $this->cacheManager->getBrowserPath($images[0]->getPath(), 'sylius_shop_product_large_thumbnail');
    }
}
