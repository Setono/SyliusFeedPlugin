<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Product;

use Doctrine\Common\Collections\ArrayCollection;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Product\MainImageResolver;
use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class MainImageResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        $resolver = new MainImageResolver($this->prophesize(CacheManager::class)->reveal());

        self::assertSame('main_image', $resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.main_image', $resolver->getLabel());
        self::assertSame(FieldType::IMAGE, $resolver->getType());
        self::assertTrue($resolver->supports(ProductVariantInterface::class));
        self::assertFalse($resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_filtered_main_image_url(): void
    {
        $image = $this->prophesize(ProductImageInterface::class);
        $image->getPath()->willReturn('main.jpg');

        $product = $this->prophesize(ProductInterface::class);
        $product->getImages()->willReturn(new ArrayCollection([$image->reveal()]));

        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getProduct()->willReturn($product->reveal());

        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->getBrowserPath('main.jpg', 'sylius_shop_product_large_thumbnail')
            ->willReturn('https://example.com/media/cache/large/main.jpg');

        $resolver = new MainImageResolver($cacheManager->reveal());

        self::assertSame(
            'https://example.com/media/cache/large/main.jpg',
            $resolver->resolve($variant->reveal(), new FeedContext()),
        );
    }

    /**
     * @test
     */
    public function it_returns_null_when_there_is_no_image(): void
    {
        $product = $this->prophesize(ProductInterface::class);
        $product->getImages()->willReturn(new ArrayCollection());

        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getProduct()->willReturn($product->reveal());

        $resolver = new MainImageResolver($this->prophesize(CacheManager::class)->reveal());

        self::assertNull($resolver->resolve($variant->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        $resolver = new MainImageResolver($this->prophesize(CacheManager::class)->reveal());

        self::assertNull($resolver->resolve(new \stdClass(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_variant_has_no_product(): void
    {
        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getProduct()->willReturn(null);

        $resolver = new MainImageResolver($this->prophesize(CacheManager::class)->reveal());

        self::assertNull($resolver->resolve($variant->reveal(), new FeedContext()));
    }
}
