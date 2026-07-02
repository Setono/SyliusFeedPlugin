<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Transformation\LiteralTransformation;

final class LiteralTransformationTest extends TestCase
{
    private FeedItem $item;

    private LiteralTransformation $transformation;

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
        $this->transformation = new LiteralTransformation();
    }

    /**
     * @test
     */
    public function it_has_the_literal_type(): void
    {
        self::assertSame('literal', $this->transformation->getType());
        self::assertSame('literal', LiteralTransformation::of('new')->getType());
        self::assertSame(['value' => 'new'], LiteralTransformation::of('new')->getParams());
    }

    /**
     * @test
     */
    public function it_always_returns_the_configured_value_ignoring_the_input(): void
    {
        self::assertSame('new', $this->transformation->apply('used', ['value' => 'new'], $this->item));
        self::assertSame('new', $this->transformation->apply(null, ['value' => 'new'], $this->item));
        self::assertSame('new', $this->transformation->apply(['whatever'], ['value' => 'new'], $this->item));
    }

    /**
     * @test
     */
    public function it_returns_null_when_no_value_is_configured(): void
    {
        self::assertNull($this->transformation->apply('used', [], $this->item));
    }
}
