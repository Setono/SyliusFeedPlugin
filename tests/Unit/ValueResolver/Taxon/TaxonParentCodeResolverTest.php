<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Taxon;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Taxon\TaxonParentCodeResolver;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

final class TaxonParentCodeResolverTest extends TestCase
{
    use ProphecyTrait;

    private TaxonParentCodeResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TaxonParentCodeResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('parent_code', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.parent_code', $this->resolver->getLabel());
        self::assertSame(FieldType::STRING, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(TaxonInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_parents_code(): void
    {
        $parent = $this->prophesize(TaxonInterface::class);
        $parent->getCode()->willReturn('CATEGORY-ROOT');

        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getParent()->willReturn($parent->reveal());

        self::assertSame('CATEGORY-ROOT', $this->resolver->resolve($taxon->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_a_root_taxon(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getParent()->willReturn(null);

        self::assertNull($this->resolver->resolve($taxon->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
