<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Product;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Product\ProductUrlResolver;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductTranslationInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @covers \Setono\SyliusFeedPlugin\ValueResolver\Product\ProductUrlResolver
 */
final class ProductUrlResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        $resolver = new ProductUrlResolver($this->prophesize(UrlGeneratorInterface::class)->reveal());

        self::assertSame('link', $resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.link', $resolver->getLabel());
        self::assertSame(FieldType::URL, $resolver->getType());
        self::assertTrue($resolver->supports(ProductVariantInterface::class));
        self::assertFalse($resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_generates_an_absolute_localized_product_url(): void
    {
        $translation = $this->prophesize(ProductTranslationInterface::class);
        $translation->getSlug()->willReturn('acme-shoe');

        $product = $this->prophesize(ProductInterface::class);
        $product->getTranslation('en_US')->willReturn($translation->reveal());

        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getProduct()->willReturn($product->reveal());

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(
            'sylius_shop_product_show',
            ['slug' => 'acme-shoe', '_locale' => 'en_US'],
            UrlGeneratorInterface::ABSOLUTE_URL,
        )->willReturn('https://example.com/en_US/products/acme-shoe');

        $resolver = new ProductUrlResolver($urlGenerator->reveal());

        self::assertSame(
            'https://example.com/en_US/products/acme-shoe',
            $resolver->resolve($variant->reveal(), new FeedContext(null, 'en_US')),
        );
    }

    /**
     * @test
     */
    public function it_returns_null_without_a_locale(): void
    {
        $resolver = new ProductUrlResolver($this->prophesize(UrlGeneratorInterface::class)->reveal());

        self::assertNull($resolver->resolve($this->prophesize(ProductVariantInterface::class)->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_variant_has_no_product(): void
    {
        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getProduct()->willReturn(null);

        $resolver = new ProductUrlResolver($this->prophesize(UrlGeneratorInterface::class)->reveal());

        self::assertNull($resolver->resolve($variant->reveal(), new FeedContext(null, 'en_US')));
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_product_has_no_slug(): void
    {
        $translation = $this->prophesize(ProductTranslationInterface::class);
        $translation->getSlug()->willReturn(null);

        $product = $this->prophesize(ProductInterface::class);
        $product->getTranslation('en_US')->willReturn($translation->reveal());

        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getProduct()->willReturn($product->reveal());

        $resolver = new ProductUrlResolver($this->prophesize(UrlGeneratorInterface::class)->reveal());

        self::assertNull($resolver->resolve($variant->reveal(), new FeedContext(null, 'en_US')));
    }
}
