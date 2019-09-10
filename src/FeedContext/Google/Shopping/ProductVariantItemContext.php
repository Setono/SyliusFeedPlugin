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
use Setono\SyliusFeedPlugin\Model\ColorAwareInterface;
use Setono\SyliusFeedPlugin\Model\ConditionAwareInterface;
use Setono\SyliusFeedPlugin\Model\GtinAwareInterface;
use Setono\SyliusFeedPlugin\Model\MpnAwareInterface;
use Setono\SyliusFeedPlugin\Model\SizeAwareInterface;
use Sylius\Component\Core\Calculator\ProductVariantPriceCalculatorInterface;
use Sylius\Component\Core\Exception\MissingChannelConfigurationException;
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

        [$price, $salePrice] = $this->getPrices($variant, $channel);

        $data = new Product($variant->getCode(), $translation->getName(), $productTranslation->getDescription(), $link,
            $imageLink, $this->getAvailability($variant), $price);

        $data->setSalePrice($salePrice);

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

        if ($variant instanceof MpnAwareInterface && $variant->getMpn() !== null) {
            $data->setMpn((string) $variant->getMpn());
        }

        if ($variant instanceof SizeAwareInterface && $variant->getSize() !== null) {
            $data->setSize((string) $variant->getSize());
        }

        if ($variant instanceof ColorAwareInterface && $variant->getColor() !== null) {
            $data->setColor((string) $variant->getColor());
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
     * Index 0 equals the price
     * Index 1 equals the sale price (if set)
     *
     * @throws StringsException
     */
    private function getPrices(ProductVariantInterface $variant, ChannelInterface $channel): array
    {
        $channelPricing = $variant->getChannelPricingForChannel($channel);

        if (null === $channelPricing) {
            throw new MissingChannelConfigurationException(sprintf(
                'Channel %s has no price defined for product variant %s',
                $channel->getName(),
                $variant->getName()
            ));
        }

        $originalPrice = $channelPricing->getOriginalPrice();
        $price = $channelPricing->getPrice();

        if (null === $originalPrice) {
            return [$this->createPrice($price, $channel), null];
        }

        return [$this->createPrice($originalPrice, $channel), $this->createPrice($price, $channel)];
    }

    /**
     * @throws StringsException
     */
    private function createPrice(int $price, ChannelInterface $channel): Price
    {
        $baseCurrency = $channel->getBaseCurrency();
        if (null === $baseCurrency) {
            throw new InvalidArgumentException(sprintf('No base currency set on channel %s', $channel->getCode()));
        }

        return new Price($price, $baseCurrency);
    }
}
