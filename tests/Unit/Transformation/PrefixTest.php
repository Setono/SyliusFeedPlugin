<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Transformation\Prefix;

final class PrefixTest extends TestCase
{
    private FeedItem $item;

    private Prefix $transformation;

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
        $this->transformation = new Prefix();
    }

    /**
     * @test
     */
    public function it_has_the_prefix_type(): void
    {
        self::assertSame('prefix', $this->transformation->getType());
        self::assertSame('prefix', Prefix::with('SKU-')->getType());
        self::assertSame(['text' => 'SKU-'], Prefix::with('SKU-')->getParams());
    }

    /**
     * @test
     */
    public function it_prepends_the_text(): void
    {
        self::assertSame('SKU-123', $this->transformation->apply('123', ['text' => 'SKU-'], $this->item));
    }

    /**
     * @test
     */
    public function it_maps_element_wise_over_a_list(): void
    {
        self::assertSame(
            ['SKU-1', 'SKU-2'],
            $this->transformation->apply(['1', '2'], ['text' => 'SKU-'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_passes_non_strings_through_unchanged(): void
    {
        self::assertSame(123, $this->transformation->apply(123, ['text' => 'SKU-'], $this->item));
    }

    /**
     * @test
     */
    public function it_is_a_no_op_when_text_is_missing(): void
    {
        self::assertSame('123', $this->transformation->apply('123', [], $this->item));
    }
}
