<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Transformation\NumberFormat;

final class NumberFormatTest extends TestCase
{
    private FeedItem $item;

    private NumberFormat $transformation;

    protected function setUp(): void
    {
        $this->item = new FeedItem(new \stdClass(), new FeedContext());
        $this->transformation = new NumberFormat();
    }

    /**
     * @test
     */
    public function it_has_the_number_format_type(): void
    {
        self::assertSame('number_format', $this->transformation->getType());
        self::assertSame('number_format', NumberFormat::decimals(2)->getType());
        self::assertSame(['decimals' => 2], NumberFormat::decimals(2)->getParams());
        self::assertSame(
            ['decimals' => 2, 'decimalSep' => ',', 'thousandsSep' => '.', 'currency' => 'EUR'],
            NumberFormat::decimals(2, ',', '.', 'EUR')->getParams(),
        );
    }

    /**
     * @test
     */
    public function it_formats_with_the_default_separators(): void
    {
        self::assertSame('1,234.50', $this->transformation->apply(1234.5, ['decimals' => 2], $this->item));
    }

    /**
     * @test
     */
    public function it_formats_with_custom_separators(): void
    {
        self::assertSame(
            '1.234,50',
            $this->transformation->apply(1234.5, ['decimals' => 2, 'decimalSep' => ',', 'thousandsSep' => '.'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_appends_a_currency_when_provided(): void
    {
        self::assertSame(
            '9.99 EUR',
            $this->transformation->apply(9.99, ['decimals' => 2, 'currency' => 'EUR'], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_maps_element_wise_over_a_list(): void
    {
        self::assertSame(
            ['1.00', '2.50'],
            $this->transformation->apply([1, 2.5], ['decimals' => 2], $this->item),
        );
    }

    /**
     * @test
     */
    public function it_accepts_numeric_strings(): void
    {
        self::assertSame('9.99', $this->transformation->apply('9.99', ['decimals' => 2], $this->item));
    }

    /**
     * @test
     */
    public function it_passes_non_numeric_values_through_unchanged(): void
    {
        self::assertSame('n/a', $this->transformation->apply('n/a', ['decimals' => 2], $this->item));
    }
}
