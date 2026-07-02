<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Taxon;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Taxon\TaxonCodeResolver;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

final class TaxonCodeResolverTest extends TestCase
{
    use ProphecyTrait;

    private TaxonCodeResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TaxonCodeResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('code', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.code', $this->resolver->getLabel());
        self::assertSame(FieldType::STRING, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(TaxonInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_taxon_code(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getCode()->willReturn('CATEGORY-1');

        self::assertSame('CATEGORY-1', $this->resolver->resolve($taxon->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
