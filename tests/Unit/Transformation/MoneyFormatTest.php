<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Transformation;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Transformation\MoneyFormat;

/**
 * @covers \Setono\SyliusFeedPlugin\Transformation\MoneyFormat
 */
final class MoneyFormatTest extends TestCase
{
    private MoneyFormat $transformation;

    protected function setUp(): void
    {
        $this->transformation = new MoneyFormat();
    }

    private function item(?string $currencyCode): FeedItem
    {
        return new FeedItem(new \stdClass(), new FeedContext(null, null, $currencyCode));
    }

    /**
     * @test
     */
    public function it_has_the_money_format_type(): void
    {
        self::assertSame('money_format', $this->transformation->getType());
        self::assertSame('money_format', MoneyFormat::withCurrency()->getType());
        self::assertSame(['decimals' => 2], MoneyFormat::withCurrency()->getParams());
    }

    /**
     * @test
     */
    public function it_formats_minor_units_using_the_context_currency(): void
    {
        self::assertSame('9.99 USD', $this->transformation->apply(999, [], $this->item('USD')));
    }

    /**
     * @test
     */
    public function it_prefers_an_explicit_currency_param(): void
    {
        self::assertSame('9.99 EUR', $this->transformation->apply(999, ['currency' => 'EUR'], $this->item('USD')));
    }

    /**
     * @test
     */
    public function it_omits_the_currency_when_none_is_available(): void
    {
        self::assertSame('9.99', $this->transformation->apply(999, [], $this->item(null)));
    }

    /**
     * @test
     */
    public function it_maps_element_wise_over_a_list(): void
    {
        self::assertSame(['9.99 USD', '10.99 USD'], $this->transformation->apply([999, 1099], [], $this->item('USD')));
    }

    /**
     * @test
     */
    public function it_passes_non_numeric_values_through_unchanged(): void
    {
        self::assertSame('n/a', $this->transformation->apply('n/a', [], $this->item('USD')));
    }
}
