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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ProductNormalizer implements NormalizerInterface
{
    /** @var RouterInterface */
    private $router;

    /**
     * @var CacheManager
     */
    private $cacheManager;
    /** @var NormalizerInterface */
    private $variantNormalizer;

    public function __construct(RouterInterface $router, CacheManager $cacheManager, NormalizerInterface $variantNormalizer)
    {
        $this->router = $router;
        $this->cacheManager = $cacheManager;
        $this->variantNormalizer = $variantNormalizer;
    }

    /**
     * @throws StringsException
     */
    public function normalize(object $product, string $channel, string $locale): array
    {
        if (!$product instanceof ProductInterface) {
            throw new InvalidArgumentException(sprintf('The class %s is not an instance of %s', get_class($product), ProductInterface::class));
        }

        $translation = $product->getTranslation($locale);

        $data = new Product();
        $data->id = $product->getCode();
        $data->title = $translation->getName();
        $data->description = $translation->getDescription();
        $data->link = $this->router->generate(
            'sylius_shop_product_show',
            ['slug' => $translation->getSlug(), '_locale' => $locale],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $data->availability = 'out of stock';
//        $data->price = ''; todo
        $data->condition = $product instanceof ConditionAwareInterface ? (string) $product->getCondition() : 'new';
        $data->itemGroupId = $product->getCode();

        $imageUrl = $this->getImageUrl($product);
        if(null !== $imageUrl) {
            $data->imageLink = $imageUrl;
        }

        if ($product instanceof BrandAwareInterface) {
            $data->brand = (string) $product->getBrand();
        }

        if ($product instanceof GtinAwareInterface) {
            $data->gtin = (string) $product->getGtin();
        }

        $items = [$data];

        foreach ($product->getVariants() as $variant) {
            $normalizedVariants = $this->variantNormalizer->normalize($variant, $channel, $locale);
            foreach ($normalizedVariants as $normalizedVariant) {
                $items[] = $normalizedVariant;
            }
        }

        // todo fire event

        return $items;
    }

    private function getImageUrl(ImagesAwareInterface $imagesAware): ?string
    {
        $images = $imagesAware->getImagesByType('main');
        if($images->count() === 0) {
            $images = $imagesAware->getImages();
        }

        if($images->count() === 0) {
            return null;
        }

        return $this->cacheManager->getBrowserPath($images[0]->getPath(), 'sylius_shop_product_large_thumbnail');
    }
}
