<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Reference;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Reference\ReferenceResolver;

final class ReferenceResolverTest extends TestCase
{
    private function item(): FeedItem
    {
        return new FeedItem(new \stdClass(), new FeedContext());
    }

    /**
     * @test
     */
    public function it_resolves_the_special_value_reference_to_the_current_value(): void
    {
        self::assertSame('current', (new ReferenceResolver())->resolve('value', 'current', $this->item()));
    }

    /**
     * @test
     */
    public function it_resolves_single_and_double_quoted_literals_to_their_inner_text(): void
    {
        $resolver = new ReferenceResolver();
        $item = $this->item();

        self::assertSame('hello', $resolver->resolve("'hello'", null, $item));
        self::assertSame('hello', $resolver->resolve('"hello"', null, $item));
        self::assertSame('', $resolver->resolve("''", null, $item));
        // "value" as a quoted literal is the string, not the current value
        self::assertSame('value', $resolver->resolve('"value"', 'current', $item));
    }

    /**
     * @test
     */
    public function it_resolves_an_earlier_output_field_from_the_bag(): void
    {
        $item = $this->item();
        $item->set('g:price', '199.00');

        self::assertSame('199.00', (new ReferenceResolver())->resolve('g:price', null, $item));
    }

    /**
     * @test
     */
    public function it_prefers_the_output_bag_over_source_resolution(): void
    {
        $item = $this->item();
        $item->set('title', 'from bag');
        $item->setSourceResolver(static fn (string $field): string => 'from source');

        self::assertSame('from bag', (new ReferenceResolver())->resolve('title', null, $item));
    }

    /**
     * @test
     */
    public function it_resolves_a_source_field_on_demand_when_absent_from_the_bag(): void
    {
        $item = $this->item();
        $item->setSourceResolver(static fn (string $field): string => 'resolved:' . $field);

        self::assertSame('resolved:lookup:badges:suffix', (new ReferenceResolver())->resolve('lookup:badges:suffix', null, $item));
    }

    /**
     * @test
     */
    public function it_returns_null_for_an_unknown_reference_without_a_source_resolver(): void
    {
        self::assertNull((new ReferenceResolver())->resolve('nope', null, $this->item()));
    }
}
