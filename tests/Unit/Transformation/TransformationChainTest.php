<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Transformation\StripTags;
use Setono\SyliusFeedPlugin\Transformation\TransformationChain;
use Setono\SyliusFeedPlugin\Transformation\TransformationRegistry;
use Setono\SyliusFeedPlugin\Transformation\Truncate;

/**
 * @covers \Setono\SyliusFeedPlugin\Transformation\TransformationChain
 */
final class TransformationChainTest extends TestCase
{
    private FeedItem $item;

    private TransformationChain $chain;

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
        $this->chain = new TransformationChain(new TransformationRegistry([new StripTags(), new Truncate()]));
    }

    /**
     * @test
     */
    public function it_applies_each_transformation_in_order(): void
    {
        $result = $this->chain->apply(
            '<p>Hello world</p>',
            [StripTags::all(), Truncate::chars(5)],
            $this->item,
        );

        self::assertSame('Hello', $result);
    }

    /**
     * @test
     */
    public function it_returns_the_value_unchanged_for_an_empty_chain(): void
    {
        self::assertSame('untouched', $this->chain->apply('untouched', [], $this->item));
    }
}
