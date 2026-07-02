<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Taxon;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Taxon\TaxonNameResolver;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;

final class TaxonNameResolverTest extends TestCase
{
    use ProphecyTrait;

    private TaxonNameResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TaxonNameResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('name', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.name', $this->resolver->getLabel());
        self::assertSame(FieldType::STRING, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(TaxonInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_translated_taxon_name(): void
    {
        $translation = $this->prophesize(TaxonTranslationInterface::class);
        $translation->getName()->willReturn('Shoes');

        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getTranslation('en_US')->willReturn($translation->reveal());

        self::assertSame('Shoes', $this->resolver->resolve($taxon->reveal(), new FeedContext(null, 'en_US')));
    }

    /**
     * @test
     */
    public function it_returns_null_without_a_locale(): void
    {
        self::assertNull($this->resolver->resolve($this->prophesize(TaxonInterface::class)->reveal(), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unsupported_entity(): void
    {
        self::assertNull($this->resolver->resolve(new \stdClass(), new FeedContext(null, 'en_US')));
    }
}
