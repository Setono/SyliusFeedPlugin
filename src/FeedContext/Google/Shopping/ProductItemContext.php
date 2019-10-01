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
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ImagesAwareInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ProductItemContext implements ItemContextInterface
{
    /** @var RouterInterface */
    private $router;

    /** @var CacheManager */
    private $cacheManager;

    /** @var ItemContextInterface */
    private $productVariantItemContext;

    /** @var ProductVariantPriceCalculatorInterface */
    private $productVariantPriceCalculator;

    /** @var ProductVariantResolverInterface */
    private $productVariantResolver;

    public function __construct(
        RouterInterface $router,
        CacheManager $cacheManager,
        ProductVariantResolverInterface $productVariantResolver,
        ProductVariantPriceCalculatorInterface $productVariantPriceCalculator,
        ItemContextInterface $productVariantItemContext
    ) {
        $this->router = $router;
        $this->cacheManager = $cacheManager;
        $this->productVariantItemContext = $productVariantItemContext;
        $this->productVariantPriceCalculator = $productVariantPriceCalculator;
        $this->productVariantResolver = $productVariantResolver;
    }

    /**
     * @throws StringsException
     */
    public function getContextList(object $product, ChannelInterface $channel, LocaleInterface $locale): ContextListInterface
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

        $data = new Product((string) $product->getCode(), (string) $translation->getName(), (string) $translation->getDescription(), $link,
            $imageLink, Availability::outOfStock(), $price);

        $data->setCondition($product instanceof ConditionAwareInterface ? Condition::fromValue($product->getCondition()) : Condition::new());
        $data->setItemGroupId($product->getCode());

        if ($product instanceof BrandAwareInterface && $product->getBrand() !== null) {
            $data->setBrand((string) $product->getBrand());
        }

        if ($product instanceof GtinAwareInterface && $product->getGtin() !== null) {
            $data->setGtin((string) $product->getGtin());
        }

        if ($product instanceof MpnAwareInterface && $product->getMpn() !== null) {
            $data->setMpn((string) $product->getMpn());
        }

        if ($product instanceof SizeAwareInterface && $product->getSize() !== null) {
            $data->setSize((string) $product->getSize());
        }

        if ($product instanceof ColorAwareInterface && $product->getColor() !== null) {
            $data->setColor((string) $product->getColor());
        }

        $contextList = new ContextList();
        $contextList->add($data);

        foreach ($product->getVariants() as $variant) {
            $variantContextList = $this->productVariantItemContext->getContextList($variant, $channel, $locale);
            foreach ($variantContextList as $item) {
                $contextList->add($item);
            }
        }

        return $contextList;
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
    private function getPrice(ProductInterface $product, ChannelInterface $channel): Price
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

        return new Price($price, $baseCurrency);
    }
}
