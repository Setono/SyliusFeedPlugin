<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Transformation\ValueMap;

final class ValueMapTest extends TestCase
{
    private FeedItem $item;

    private ValueMap $transformation;

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
        $this->transformation = new ValueMap();
    }

    /**
     * @test
     */
    public function it_has_the_value_map_type(): void
    {
        self::assertSame('value_map', $this->transformation->getType());
        self::assertSame('value_map', ValueMap::of(['a' => 'b'])->getType());
        self::assertSame(['map' => ['a' => 'b']], ValueMap::of(['a' => 'b'])->getParams());
        self::assertSame(
            ['map' => ['a' => 'b'], 'default' => 'z'],
            ValueMap::withDefault(['a' => 'b'], 'z')->getParams(),
        );
    }

    /**
     * @test
     */
    public function it_maps_a_matched_value(): void
    {
        self::assertSame(
            'refurbished',
            $this->transformation->apply('used', ['map' => ['used' => 'refurbished', 'new' => 'new']], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_casts_the_value_to_a_string_key(): void
    {
        self::assertSame('yes', $this->transformation->apply(1, ['map' => ['1' => 'yes']], $this->item));
    }

    /**
     * @test
     */
    public function it_falls_back_to_the_default_when_provided(): void
    {
        self::assertSame(
            'unknown',
            $this->transformation->apply('missing', ['map' => ['a' => 'b'], 'default' => 'unknown'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_returns_the_original_value_when_unmatched_and_no_default(): void
    {
        self::assertSame('missing', $this->transformation->apply('missing', ['map' => ['a' => 'b']], $this->item));
    }

    /**
     * @test
     */
    public function it_is_a_no_op_when_map_is_missing(): void
    {
        self::assertSame('value', $this->transformation->apply('value', [], $this->item));
    }
}
