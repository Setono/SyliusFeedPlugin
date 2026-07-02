<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\ValueResolver\Taxon;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\Taxon\TaxonSlugResolver;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;

final class TaxonSlugResolverTest extends TestCase
{
    use ProphecyTrait;

    private TaxonSlugResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TaxonSlugResolver();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('slug', $this->resolver->getName());
        self::assertSame('setono_sylius_feed.value_resolver.slug', $this->resolver->getLabel());
        self::assertSame(FieldType::STRING, $this->resolver->getType());
        self::assertTrue($this->resolver->supports(TaxonInterface::class));
        self::assertFalse($this->resolver->supports(\stdClass::class));
    }

    /**
     * @test
     */
    public function it_resolves_the_translated_taxon_slug(): void
    {
        $translation = $this->prophesize(TaxonTranslationInterface::class);
        $translation->getSlug()->willReturn('shoes');

        $taxon = $this->prophesize(TaxonInterface::class);
        $taxon->getTranslation('en_US')->willReturn($translation->reveal());

        self::assertSame('shoes', $this->resolver->resolve($taxon->reveal(), new FeedContext(null, 'en_US')));
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
