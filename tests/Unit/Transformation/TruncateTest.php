<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Transformation\Truncate;

/**
 * @covers \Setono\SyliusFeedPlugin\Transformation\Truncate
 */
final class TruncateTest extends TestCase
{
    private FeedItem $item;

    private Truncate $transformation;

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
        $this->transformation = new Truncate();
    }

    /**
     * @test
     */
    public function it_has_the_truncate_type(): void
    {
        self::assertSame('truncate', $this->transformation->getType());
        self::assertSame('truncate', Truncate::chars(150)->getType());
        self::assertSame(['max' => 150, 'ellipsis' => ''], Truncate::chars(150)->getParams());
    }

    /**
     * @test
     */
    public function it_truncates_strings_longer_than_the_max(): void
    {
        self::assertSame('hel', $this->transformation->apply('hello', ['max' => 3], $this->item));
    }

    /**
     * @test
     */
    public function it_leaves_short_strings_untouched(): void
    {
        self::assertSame('hi', $this->transformation->apply('hi', ['max' => 3], $this->item));
    }

    /**
     * @test
     */
    public function it_keeps_the_ellipsis_within_the_max_length(): void
    {
        self::assertSame('abcd…', $this->transformation->apply('abcdefgh', ['max' => 5, 'ellipsis' => '…'], $this->item));
    }

    /**
     * @test
     */
    public function it_maps_element_wise_over_a_list(): void
    {
        self::assertSame(['hel', 'hi'], $this->transformation->apply(['hello', 'hi'], ['max' => 3], $this->item));
    }

    /**
     * @test
     */
    public function it_passes_non_strings_through_unchanged(): void
    {
        self::assertSame(123, $this->transformation->apply(123, ['max' => 3], $this->item));
        self::assertNull($this->transformation->apply(null, ['max' => 3], $this->item));
    }
}
