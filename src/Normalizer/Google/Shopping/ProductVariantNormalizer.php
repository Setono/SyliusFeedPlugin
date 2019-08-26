<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Normalizer\Google\Shopping;

use InvalidArgumentException;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Normalizer\NormalizerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ProductVariantNormalizer implements NormalizerInterface
{
    /** @var AvailabilityCheckerInterface */
    private $availabilityChecker;

    /** @var RouterInterface */
    private $router;

    public function __construct(AvailabilityCheckerInterface $availabilityChecker, RouterInterface $router)
    {
        $this->availabilityChecker = $availabilityChecker;
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

        $product = $variant->getProduct();

        if (null === $product) {
            throw new InvalidArgumentException(sprintf('The variant "%s" does not have a product associated with it', $variant->getCode()));
        }

        $translation = $variant->getTranslation($locale);
        $productTranslation = $product->getTranslation($locale);

        $data = [
            'id' => $variant->getCode(),
            'title' => $translation->getName(),
            'description' => $productTranslation->getDescription(),
            'link' => $this->router->generate(
                'sylius_shop_product_show',
                ['slug' => $productTranslation->getSlug(), '_locale' => $locale],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
//            'image_link' => $variant, // todo
            'availability' => $this->getAvailability($variant), // todo
//            'price' => $variant, // todo
//            'brand' => $variant, // todo
//            'gtin' => $variant, // todo
            'condition' => 'new',
            'item_group_id' => $product->getCode(),
        ];

        return $data;
    }

    private function getAvailability(ProductVariantInterface $product): string
    {
        return $this->availabilityChecker->isStockAvailable($product) ? 'in stock' : 'out of stock';
    }
}
