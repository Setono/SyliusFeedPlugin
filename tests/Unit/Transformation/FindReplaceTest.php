<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Transformation\FindReplace;

final class FindReplaceTest extends TestCase
{
    private FeedItem $item;

    private FindReplace $transformation;

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
        $this->transformation = new FindReplace();
    }

    /**
     * @test
     */
    public function it_has_the_find_replace_type(): void
    {
        self::assertSame('find_replace', $this->transformation->getType());
        self::assertSame('find_replace', FindReplace::of('a', 'b')->getType());
        self::assertSame(['search' => 'a', 'replace' => 'b'], FindReplace::of('a', 'b')->getParams());
    }

    /**
     * @test
     */
    public function it_replaces_every_occurrence(): void
    {
        self::assertSame(
            'hxllo world',
            $this->transformation->apply('hello world', ['search' => 'e', 'replace' => 'x'], $this->item),
        );
        self::assertSame(
            'ha ha ha',
            $this->transformation->apply('lo lo lo', ['search' => 'lo', 'replace' => 'ha'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_maps_element_wise_over_a_list(): void
    {
        self::assertSame(
            ['f00', 'bar'],
            $this->transformation->apply(['foo', 'bar'], ['search' => 'o', 'replace' => '0'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_passes_non_strings_through_unchanged(): void
    {
        self::assertSame(123, $this->transformation->apply(123, ['search' => 'a', 'replace' => 'b'], $this->item));
    }

    /**
     * @test
     */
    public function it_is_a_no_op_when_search_or_replace_is_not_a_string(): void
    {
        self::assertSame('hello', $this->transformation->apply('hello', ['search' => ['e'], 'replace' => 'x'], $this->item));
        self::assertSame('hello', $this->transformation->apply('hello', [], $this->item));
    }
}
