<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Product;

use Doctrine\Common\Collections\ArrayCollection;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Product\AdditionalImagesResolver;
use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class AdditionalImagesResolverTest extends TestCase
{
    use ProphecyTrait;

    private function image(string $path): ProductImageInterface
    {
        $image = $this->prophesize(ProductImageInterface::class);
        $image->getPath()->willReturn($path);

        return $image->reveal();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        $resolver = new AdditionalImagesResolver($this->prophesize(CacheManager::class)->reveal());

        self::assertSame('additional_images', $resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.additional_images', $resolver->getLabel());
        self::assertSame(FieldType::IMAGE, $resolver->getType());
        self::assertTrue($resolver->supports(ProductVariantInterface::class));
        self::assertFalse($resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_returns_an_empty_list_when_the_variant_has_no_product(): void
    {
        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getProduct()->willReturn(null);

        $resolver = new AdditionalImagesResolver($this->prophesize(CacheManager::class)->reveal());

        self::assertSame([], $resolver->resolve($variant->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_resolves_all_but_the_first_image(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getImages()->willReturn(new ArrayCollection([
            $this->image('main.jpg'),
            $this->image('extra-1.jpg'),
            $this->image('extra-2.jpg'),
        ]));

        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getProduct()->willReturn($product->reveal());

        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->getBrowserPath('extra-1.jpg', 'sylius_shop_product_large_thumbnail')->willReturn('https://example.com/extra-1.jpg');
        $cacheManager->getBrowserPath('extra-2.jpg', 'sylius_shop_product_large_thumbnail')->willReturn('https://example.com/extra-2.jpg');

        $resolver = new AdditionalImagesResolver($cacheManager->reveal());

        self::assertSame(
            ['https://example.com/extra-1.jpg', 'https://example.com/extra-2.jpg'],
            $resolver->resolve($variant->reveal(), new FeedContext()),
        );
    }

    /**
     * @test
     */
    public function it_returns_an_empty_list_for_an_unsupported_entity(): void
    {
        $resolver = new AdditionalImagesResolver($this->prophesize(CacheManager::class)->reveal());

        self::assertSame([], $resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
