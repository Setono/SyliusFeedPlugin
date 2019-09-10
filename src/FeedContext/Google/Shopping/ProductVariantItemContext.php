<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedContext\Google\Shopping;

use InvalidArgumentException;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping\Availability;
use Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping\Condition;
use Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping\Price;
use Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping\Product;
use Setono\SyliusFeedPlugin\FeedContext\ContextList;
use Setono\SyliusFeedPlugin\FeedContext\ContextListInterface;
use Setono\SyliusFeedPlugin\FeedContext\ItemContextInterface;
use Setono\SyliusFeedPlugin\Model\BrandAwareInterface;
use Setono\SyliusFeedPlugin\Model\ConditionAwareInterface;
use Setono\SyliusFeedPlugin\Model\GtinAwareInterface;
use Sylius\Component\Core\Calculator\ProductVariantPriceCalculatorInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ImagesAwareInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ProductVariantItemContext implements ItemContextInterface
{
    /** @var AvailabilityCheckerInterface */
    private $availabilityChecker;

    /** @var CacheManager */
    private $cacheManager;

    /** @var RouterInterface */
    private $router;

    /** @var ProductVariantPriceCalculatorInterface */
    private $productVariantPriceCalculator;

    public function __construct(
        AvailabilityCheckerInterface $availabilityChecker,
        CacheManager $cacheManager,
        RouterInterface $router,
        ProductVariantPriceCalculatorInterface $productVariantPriceCalculator
    ) {
        $this->availabilityChecker = $availabilityChecker;
        $this->cacheManager = $cacheManager;
        $this->router = $router;
        $this->productVariantPriceCalculator = $productVariantPriceCalculator;
    }

    /**
     * @throws StringsException
     */
    public function getContextList(object $variant, ChannelInterface $channel, LocaleInterface $locale): ContextListInterface
    {
        if (!$variant instanceof ProductVariantInterface) {
            throw new InvalidArgumentException(sprintf('The class %s is not an instance of %s', get_class($variant),
                ProductVariantInterface::class));
        }

        /** @var ProductInterface|null $product */
        $product = $variant->getProduct();

        if (null === $product) {
            throw new InvalidArgumentException(sprintf(
                'The variant "%s" does not have a product associated with it',
                $variant->getCode()
            ));
        }

        $translation = $variant->getTranslation($locale->getCode());
        $productTranslation = $product->getTranslation($locale->getCode());

        $link = $this->getLink($locale, $productTranslation);
        $imageLink = $this->getImageLink($product);

        $data = new Product($variant->getCode(), $translation->getName(), $productTranslation->getDescription(), $link,
            $imageLink, $this->getAvailability($variant), $this->getPrice($variant, $channel));

        $data->setCondition($variant instanceof ConditionAwareInterface ? Condition::fromValue($variant->getCondition()) : Condition::new());
        $data->setItemGroupId($product->getCode());

        if ($variant instanceof BrandAwareInterface && $variant->getBrand() !== null) {
            $data->setBrand((string) $variant->getBrand());
        } elseif ($product instanceof BrandAwareInterface && $product->getBrand() !== null) {
            $data->setBrand((string) $product->getBrand());
        }

        if ($variant instanceof GtinAwareInterface && $variant->getGtin() !== null) {
            $data->setGtin((string) $variant->getGtin());
        }

        return new ContextList([$data]);
    }

    private function getAvailability(ProductVariantInterface $product): Availability
    {
        return $this->availabilityChecker->isStockAvailable($product) ? Availability::inStock() : Availability::outOfStock();
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

    private function getLink(LocaleInterface $locale, ProductTranslationInterface $productTranslation): string
    {
        return $this->router->generate(
            'sylius_shop_product_show',
            ['slug' => $productTranslation->getSlug(), '_locale' => $locale->getCode()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @throws StringsException
     */
    private function getPrice(ProductVariantInterface $variant, ChannelInterface $channel): Price
    {
        $price = $this->productVariantPriceCalculator->calculate($variant, ['channel' => $channel]);
        $baseCurrency = $channel->getBaseCurrency();
        if (null === $baseCurrency) {
            throw new InvalidArgumentException(sprintf('No base currency set on channel %s', $channel->getCode()));
        }

        return new Price($price, $baseCurrency);
    }
}
