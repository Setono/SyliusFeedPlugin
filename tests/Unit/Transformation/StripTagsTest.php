<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Transformation\StripTags;

final class StripTagsTest extends TestCase
{
    private FeedItem $item;

    private StripTags $transformation;

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
        $this->transformation = new StripTags();
    }

    /**
     * @test
     */
    public function it_has_the_strip_tags_type(): void
    {
        self::assertSame('strip_tags', $this->transformation->getType());
        self::assertSame('strip_tags', StripTags::all()->getType());
        self::assertSame(['allowed' => ['<br>', '<b>']], StripTags::allowed('<br>', '<b>')->getParams());
    }

    /**
     * @test
     */
    public function it_strips_all_tags_by_default(): void
    {
        self::assertSame('Hello world', $this->transformation->apply('<p>Hello <b>world</b></p>', [], $this->item));
    }

    /**
     * @test
     */
    public function it_keeps_allowed_tags(): void
    {
        self::assertSame(
            'Hello <b>world</b>',
            $this->transformation->apply('<p>Hello <b>world</b></p>', ['allowed' => ['<b>']], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_maps_element_wise_over_a_list(): void
    {
        self::assertSame(['a', 'b'], $this->transformation->apply(['<i>a</i>', '<i>b</i>'], [], $this->item));
    }

    /**
     * @test
     */
    public function it_passes_non_strings_through_unchanged(): void
    {
        self::assertSame(42, $this->transformation->apply(42, [], $this->item));
    }
}
