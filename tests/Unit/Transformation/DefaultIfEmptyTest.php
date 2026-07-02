<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Reference\ReferenceResolver;
use Setono\SyliusFeedPlugin\Transformation\DefaultIfEmpty;

final class DefaultIfEmptyTest extends TestCase
{
    private FeedItem $item;

    private DefaultIfEmpty $transformation;

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
        $this->transformation = new DefaultIfEmpty(new ReferenceResolver());
    }

    /**
     * @test
     */
    public function it_has_the_default_if_empty_type(): void
    {
        self::assertSame('default_if_empty', $this->transformation->getType());
        self::assertSame('default_if_empty', DefaultIfEmpty::of("'n/a'")->getType());
        self::assertSame(['value' => "'n/a'"], DefaultIfEmpty::of("'n/a'")->getParams());
    }

    /**
     * @test
     */
    public function it_returns_the_value_unchanged_when_not_empty(): void
    {
        self::assertSame('hello', $this->transformation->apply('hello', ['value' => "'n/a'"], $this->item));
    }

    /**
     * @test
     */
    public function it_falls_back_to_a_literal_when_null(): void
    {
        self::assertSame('n/a', $this->transformation->apply(null, ['value' => "'n/a'"], $this->item));
    }

    /**
     * @test
     */
    public function it_falls_back_when_an_empty_string(): void
    {
        self::assertSame('n/a', $this->transformation->apply('', ['value' => "'n/a'"], $this->item));
    }

    /**
     * @test
     */
    public function it_falls_back_when_an_empty_array(): void
    {
        self::assertSame('n/a', $this->transformation->apply([], ['value' => "'n/a'"], $this->item));
    }

    /**
     * @test
     */
    public function it_resolves_an_earlier_output_bag_value_as_the_fallback(): void
    {
        $this->item->set('g:title', 'Fallback title');

        self::assertSame('Fallback title', $this->transformation->apply(null, ['value' => 'g:title'], $this->item));
    }

    /**
     * @test
     */
    public function it_resolves_a_source_field_as_the_fallback(): void
    {
        $this->item->setSourceResolver(fn (string $field): mixed => 'default_title' === $field ? 'Source title' : null);

        self::assertSame('Source title', $this->transformation->apply(null, ['value' => 'default_title'], $this->item));
    }
}
