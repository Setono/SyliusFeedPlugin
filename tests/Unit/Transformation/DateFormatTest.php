<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Transformation\DateFormat;

final class DateFormatTest extends TestCase
{
    private FeedItem $item;

    private DateFormat $transformation;

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
        $this->transformation = new DateFormat();
    }

    /**
     * @test
     */
    public function it_has_the_date_format_type(): void
    {
        self::assertSame('date_format', $this->transformation->getType());
        self::assertSame('date_format', DateFormat::of('Y-m-d')->getType());
        self::assertSame(['format' => 'Y-m-d'], DateFormat::of('Y-m-d')->getParams());
        self::assertSame(
            ['format' => 'Y-m-d', 'timezone' => 'UTC'],
            DateFormat::of('Y-m-d', 'UTC')->getParams(),
        );
    }

    /**
     * @test
     */
    public function it_formats_a_date_time_interface(): void
    {
        $date = new \DateTimeImmutable('2024-03-15 10:00:00');

        self::assertSame('2024-03-15', $this->transformation->apply($date, ['format' => 'Y-m-d'], $this->item));
    }

    /**
     * @test
     */
    public function it_parses_and_formats_a_date_string(): void
    {
        self::assertSame(
            '2024-03-15',
            $this->transformation->apply('2024-03-15 10:00:00', ['format' => 'Y-m-d'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_converts_the_timezone_when_provided(): void
    {
        $date = new \DateTimeImmutable('2024-03-15 10:00:00', new \DateTimeZone('UTC'));

        self::assertSame(
            '11:00',
            $this->transformation->apply($date, ['format' => 'H:i', 'timezone' => 'Europe/Copenhagen'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_maps_element_wise_over_a_list(): void
    {
        self::assertSame(
            ['2024-01-01', '2024-02-01'],
            $this->transformation->apply(['2024-01-01', '2024-02-01'], ['format' => 'Y-m-d'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_is_a_no_op_for_an_empty_string(): void
    {
        self::assertSame('', $this->transformation->apply('', ['format' => 'Y-m-d'], $this->item));
    }

    /**
     * @test
     */
    public function it_is_a_no_op_for_an_unparseable_string(): void
    {
        self::assertSame('not a date', $this->transformation->apply('not a date', ['format' => 'Y-m-d'], $this->item));
    }

    /**
     * @test
     */
    public function it_is_a_no_op_for_non_date_non_string_values(): void
    {
        self::assertSame(123, $this->transformation->apply(123, ['format' => 'Y-m-d'], $this->item));
    }

    /**
     * @test
     */
    public function it_is_a_no_op_when_format_is_missing(): void
    {
        $date = new \DateTimeImmutable('2024-03-15');

        self::assertSame($date, $this->transformation->apply($date, [], $this->item));
    }
}
