<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Product;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Product\TitleResolver;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductTranslationInterface;

/**
 * @covers \Setono\SyliusFeedPlugin\ValueResolver\Product\TitleResolver
 */
final class TitleResolverTest extends TestCase
{
    use ProphecyTrait;

    private TitleResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TitleResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('title', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.title', $this->resolver->getLabel());
        self::assertSame(FieldType::STRING, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(ProductVariantInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_translated_product_name(): void
    {
        $translation = $this->prophesize(ProductTranslationInterface::class);
        $translation->getName()->willReturn('Acme Shoe');

        $product = $this->prophesize(ProductInterface::class);
        $product->getTranslation('en_US')->willReturn($translation->reveal());

        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getProduct()->willReturn($product->reveal());

        self::assertSame('Acme Shoe', $this->resolver->resolve($variant->reveal(), new FeedContext(null, 'en_US')));
    }

    /**
     * @test
     */
    public function it_returns_null_without_a_locale(): void
    {
        self::assertNull($this->resolver->resolve($this->prophesize(ProductVariantInterface::class)->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_when_the_variant_has_no_product(): void
    {
        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getProduct()->willReturn(null);

        self::assertNull($this->resolver->resolve($variant->reveal(), new FeedContext(null, 'en_US')));
    }
}
