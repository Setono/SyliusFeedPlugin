<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Taxon;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Taxon\TaxonPositionResolver;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

final class TaxonPositionResolverTest extends TestCase
{
    use ProphecyTrait;

    private TaxonPositionResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TaxonPositionResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('position', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.position', $this->resolver->getLabel());
        self::assertSame(FieldType::INTEGER, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(TaxonInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_taxon_position(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getPosition()->willReturn(3);

        self::assertSame(3, $this->resolver->resolve($taxon->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext()));
    }
}
