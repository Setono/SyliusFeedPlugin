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
use Sylius\Component\Core\Calculator\ProductVariantPriceCalculatorInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ImagesAwareInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ProductNormalizer implements NormalizerInterface
{
    /** @var RouterInterface */
    private $router;

    /** @var CacheManager */
    private $cacheManager;

    /** @var NormalizerInterface */
    private $variantNormalizer;

    /** @var ProductVariantPriceCalculatorInterface */
    private $productVariantPriceCalculator;

    /** @var ProductVariantResolverInterface */
    private $productVariantResolver;

    public function __construct(
        RouterInterface $router,
        CacheManager $cacheManager,
        ProductVariantResolverInterface $productVariantResolver,
        ProductVariantPriceCalculatorInterface $productVariantPriceCalculator,
        NormalizerInterface $variantNormalizer
    ) {
        $this->router = $router;
        $this->cacheManager = $cacheManager;
        $this->variantNormalizer = $variantNormalizer;
        $this->productVariantPriceCalculator = $productVariantPriceCalculator;
        $this->productVariantResolver = $productVariantResolver;
    }

    /**
     * @throws StringsException
     */
    public function normalize(object $product, ChannelInterface $channel, LocaleInterface $locale): array
    {
        if (!$product instanceof ProductInterface) {
            throw new InvalidArgumentException(sprintf(
                'The class %s is not an instance of %s', get_class($product),
                ProductInterface::class
            ));
        }

        $translation = $product->getTranslation($locale->getCode());

        $link = $this->getLink($locale, $translation);
        $imageLink = $this->getImageLink($product);
        $price = $this->getPrice($product, $channel);

        $data = new Product($product->getCode(), $translation->getName(), $translation->getDescription(), $link,
            $imageLink, Product::AVAILABILITY_OUT_OF_STOCK, $price);

        $data->setCondition($product instanceof ConditionAwareInterface ? (string) $product->getCondition() : Product::CONDITION_NEW);
        $data->setItemGroupId($product->getCode());

        if ($product instanceof BrandAwareInterface && $product->getBrand() !== null) {
            $data->setBrand((string) $product->getBrand());
        }

        if ($product instanceof GtinAwareInterface && $product->getGtin() !== null) {
            $data->setGtin((string) $product->getGtin());
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

    private function getLink(LocaleInterface $locale, ProductTranslationInterface $translation): string
    {
        return $this->router->generate(
            'sylius_shop_product_show',
            ['slug' => $translation->getSlug(), '_locale' => $locale->getCode()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function getImageLink(ImagesAwareInterface $imagesAware): string
    {
        $images = $imagesAware->getImagesByType('main');
        if ($images->count() === 0) {
            $images = $imagesAware->getImages();
        }

        if ($images->count() === 0) {
            return '';
        }

        return $this->cacheManager->getBrowserPath($images[0]->getPath(), 'sylius_shop_product_large_thumbnail');
    }

    /**
     * @throws StringsException
     */
    private function getPrice(ProductInterface $product, ChannelInterface $channel): string
    {
        /** @var ProductVariantInterface|null $variant */
        $variant = $this->productVariantResolver->getVariant($product);
        if (null === $variant) {
            throw new InvalidArgumentException(sprintf('The product %s does not have any variants. This should not be possible', $product->getCode()));
        }

        $price = $this->productVariantPriceCalculator->calculate($variant, ['channel' => $channel]);
        $baseCurrency = $channel->getBaseCurrency();
        if (null === $baseCurrency) {
            throw new InvalidArgumentException(sprintf('No base currency set on channel %s', $channel->getCode()));
        }

        return round($price / 100, 2) . ' ' . $baseCurrency->getCode();
    }
}
