<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Product;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\ValueResolver\Product\DescriptionResolver;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductTranslationInterface;

/**
 * @covers \Setono\SyliusFeedPlugin\ValueResolver\Product\DescriptionResolver
 */
final class DescriptionResolverTest extends TestCase
{
    use ProphecyTrait;

    private DescriptionResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new DescriptionResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('description', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.description', $this->resolver->getLabel());
        self::assertTrue($this->resolver->supports(ProductVariantInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_translated_product_description(): void
    {
        $translation = $this->prophesize(ProductTranslationInterface::class);
        $translation->getDescription()->willReturn('A very nice shoe');

        $product = $this->prophesize(ProductInterface::class);
        $product->getTranslation('en_US')->willReturn($translation->reveal());

        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getProduct()->willReturn($product->reveal());

        self::assertSame('A very nice shoe', $this->resolver->resolve($variant->reveal(), new FeedContext(null, 'en_US')));
    }
}
