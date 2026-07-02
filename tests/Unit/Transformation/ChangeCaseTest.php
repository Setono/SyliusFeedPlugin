<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Transformation\ChangeCase;

final class ChangeCaseTest extends TestCase
{
    private FeedItem $item;

    private ChangeCase $transformation;

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
        $this->transformation = new ChangeCase();
    }

    /**
     * @test
     */
    public function it_has_the_change_case_type(): void
    {
        self::assertSame('change_case', $this->transformation->getType());
        self::assertSame('change_case', ChangeCase::to('upper')->getType());
        self::assertSame(['case' => 'upper'], ChangeCase::to('upper')->getParams());
    }

    /**
     * @test
     */
    public function it_converts_to_upper_case(): void
    {
        self::assertSame('HELLO', $this->transformation->apply('Hello', ['case' => 'upper'], $this->item));
    }

    /**
     * @test
     */
    public function it_converts_to_lower_case(): void
    {
        self::assertSame('hello', $this->transformation->apply('Hello', ['case' => 'lower'], $this->item));
    }

    /**
     * @test
     */
    public function it_converts_to_title_case(): void
    {
        self::assertSame('Hello World', $this->transformation->apply('hello world', ['case' => 'title'], $this->item));
    }

    /**
     * @test
     */
    public function it_maps_element_wise_over_a_list(): void
    {
        self::assertSame(
            ['A', 'B'],
            $this->transformation->apply(['a', 'b'], ['case' => 'upper'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_passes_non_strings_through_unchanged(): void
    {
        self::assertSame(123, $this->transformation->apply(123, ['case' => 'upper'], $this->item));
    }

    /**
     * @test
     */
    public function it_is_a_no_op_for_an_unknown_case(): void
    {
        self::assertSame('Hello', $this->transformation->apply('Hello', ['case' => 'shout'], $this->item));
    }
}
