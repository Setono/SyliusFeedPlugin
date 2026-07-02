<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Transformation\RegexReplace;

final class RegexReplaceTest extends TestCase
{
    private FeedItem $item;

    private RegexReplace $transformation;

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
        $this->transformation = new RegexReplace();
    }

    /**
     * @test
     */
    public function it_has_the_regex_replace_type(): void
    {
        self::assertSame('regex_replace', $this->transformation->getType());
        self::assertSame('regex_replace', RegexReplace::of('[0-9]+', 'N')->getType());
        self::assertSame(
            ['pattern' => '[0-9]+', 'replacement' => 'N', 'flags' => ''],
            RegexReplace::of('[0-9]+', 'N')->getParams(),
        );
    }

    /**
     * @test
     */
    public function it_replaces_every_match(): void
    {
        self::assertSame(
            'SKU-N-N',
            $this->transformation->apply('SKU-123-456', ['pattern' => '[0-9]+', 'replacement' => 'N'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_applies_flags(): void
    {
        self::assertSame(
            'x x x',
            $this->transformation->apply('A a A', ['pattern' => 'a', 'replacement' => 'x', 'flags' => 'i'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_escapes_the_hash_delimiter_in_the_pattern(): void
    {
        self::assertSame(
            'N',
            $this->transformation->apply('#123', ['pattern' => '#[0-9]+', 'replacement' => 'N'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_maps_element_wise_over_a_list(): void
    {
        self::assertSame(
            ['N', 'N'],
            $this->transformation->apply(['123', '456'], ['pattern' => '[0-9]+', 'replacement' => 'N'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_passes_non_strings_through_unchanged(): void
    {
        self::assertSame(123, $this->transformation->apply(123, ['pattern' => '[0-9]+', 'replacement' => 'N'], $this->item));
    }

    /**
     * @test
     */
    public function it_is_a_no_op_on_an_invalid_pattern(): void
    {
        self::assertSame(
            'hello',
            $this->transformation->apply('hello', ['pattern' => '(', 'replacement' => 'x'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_is_a_no_op_when_pattern_or_replacement_is_missing(): void
    {
        self::assertSame('hello', $this->transformation->apply('hello', [], $this->item));
    }
}
