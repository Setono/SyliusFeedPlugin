<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Reference\ReferenceResolver;
use Setono\SyliusFeedPlugin\Transformation\Concat;

final class ConcatTest extends TestCase
{
    private FeedItem $item;

    private Concat $transformation;

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
        $this->transformation = new Concat(new ReferenceResolver());
    }

    /**
     * @test
     */
    public function it_has_the_concat_type(): void
    {
        self::assertSame('concat', $this->transformation->getType());
        self::assertSame('concat', Concat::of(['brand', 'title'], ' ')->getType());
        self::assertSame(['parts' => ['brand', 'title'], 'separator' => ' '], Concat::of(['brand', 'title'], ' ')->getParams());
    }

    /**
     * @test
     */
    public function it_joins_literals_with_a_separator(): void
    {
        self::assertSame(
            'Foo-Bar',
            $this->transformation->apply('ignored', ['parts' => ["'Foo'", "'Bar'"], 'separator' => '-'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_resolves_the_current_value_reference(): void
    {
        self::assertSame(
            'prefix-42',
            $this->transformation->apply(42, ['parts' => ["'prefix'", 'value'], 'separator' => '-'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_resolves_an_earlier_output_bag_value(): void
    {
        $this->item->set('g:brand', 'Acme');

        self::assertSame('Acme', $this->transformation->apply(null, ['parts' => ['g:brand']], $this->item));
    }

    /**
     * @test
     */
    public function it_resolves_a_source_field_via_the_item_source_resolver(): void
    {
        $this->item->setSourceResolver(fn (string $field): mixed => 'sku' === $field ? 'ABC-123' : null);

        self::assertSame('ABC-123', $this->transformation->apply(null, ['parts' => ['sku']], $this->item));
    }

    /**
     * @test
     */
    public function it_drops_parts_that_resolve_to_null(): void
    {
        $this->item->setSourceResolver(fn (string $field): mixed => null);

        self::assertSame(
            'AB',
            $this->transformation->apply(null, ['parts' => ["'A'", 'missing', "'B'"], 'separator' => ''], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_defaults_to_an_empty_separator(): void
    {
        self::assertSame('AB', $this->transformation->apply(null, ['parts' => ["'A'", "'B'"]], $this->item));
    }

    /**
     * @test
     */
    public function it_is_a_no_op_when_parts_are_missing(): void
    {
        self::assertSame('value', $this->transformation->apply('value', [], $this->item));
    }
}
