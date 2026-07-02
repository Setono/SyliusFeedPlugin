<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Transformation\Suffix;

final class SuffixTest extends TestCase
{
    private FeedItem $item;

    private Suffix $transformation;

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
        $this->transformation = new Suffix();
    }

    /**
     * @test
     */
    public function it_has_the_suffix_type(): void
    {
        self::assertSame('suffix', $this->transformation->getType());
        self::assertSame('suffix', Suffix::with('-NEW')->getType());
        self::assertSame(['text' => '-NEW'], Suffix::with('-NEW')->getParams());
    }

    /**
     * @test
     */
    public function it_appends_the_text(): void
    {
        self::assertSame('123-NEW', $this->transformation->apply('123', ['text' => '-NEW'], $this->item));
    }

    /**
     * @test
     */
    public function it_maps_element_wise_over_a_list(): void
    {
        self::assertSame(
            ['1-NEW', '2-NEW'],
            $this->transformation->apply(['1', '2'], ['text' => '-NEW'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_passes_non_strings_through_unchanged(): void
    {
        self::assertSame(123, $this->transformation->apply(123, ['text' => '-NEW'], $this->item));
    }

    /**
     * @test
     */
    public function it_is_a_no_op_when_text_is_missing(): void
    {
        self::assertSame('123', $this->transformation->apply('123', [], $this->item));
    }
}
