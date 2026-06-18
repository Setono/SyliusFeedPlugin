<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Product;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Product\IdResolver;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * @covers \Setono\SyliusFeedPlugin\ValueResolver\Product\IdResolver
 */
final class IdResolverTest extends TestCase
{
    use ProphecyTrait;

    private IdResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new IdResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('id', $this->resolver->getName());
        self::assertSame(FieldType::STRING, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(ProductVariantInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_variant_code(): void
    {
        $variant = $this->prophesize(ProductVariantInterface::class);
        $variant->getCode()->willReturn('VARIANT-1');

        self::assertSame('VARIANT-1', $this->resolver->resolve($variant->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
