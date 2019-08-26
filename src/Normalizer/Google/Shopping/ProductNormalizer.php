<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Normalizer\Google\Shopping;

use InvalidArgumentException;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Normalizer\NormalizerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ProductNormalizer implements NormalizerInterface
{
    /** @var RouterInterface */
    private $router;

    /** @var NormalizerInterface */
    private $variantNormalizer;

    public function __construct(RouterInterface $router, NormalizerInterface $variantNormalizer)
    {
        $this->router = $router;
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

        $data = [];

        $data[] = [
            'id' => $product->getCode(),
            'title' => $translation->getName(),
            'description' => $translation->getDescription(),
            'link' => $this->router->generate(
                'sylius_shop_product_show',
                ['slug' => $translation->getSlug(), '_locale' => $locale],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
//            'image_link' => $product, // todo
            'availability' => 'out of stock',
//            'price' => $product, // todo
//            'brand' => $product, // todo
//            'gtin' => $product, // todo
            'condition' => 'new',
            'item_group_id' => $product->getCode(),
        ];

        foreach ($product->getVariants() as $variant) {
            $data[] = $this->variantNormalizer->normalize($variant, $channel, $locale);
        }

        // todo fire event

        return $data;
    }
}
