<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Currency;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Currency\ContextCurrencyConverter;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Currency\Converter\CurrencyConverterInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;

final class ContextCurrencyConverterTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_converts_from_the_channel_base_currency_to_the_context_currency(): void
    {
        $sylius = $this->prophesize(CurrencyConverterInterface::class);
        $sylius->convert(1000, 'USD', 'EUR')->willReturn(850);

        $converter = new ContextCurrencyConverter($sylius->reveal());

        self::assertSame(850, $converter->convert(1000, $this->channel('USD'), new FeedContext(null, null, 'EUR')));
    }

    /**
     * @test
     */
    public function it_returns_the_amount_unchanged_when_the_context_has_no_currency(): void
    {
        $converter = new ContextCurrencyConverter($this->prophesize(CurrencyConverterInterface::class)->reveal());

        self::assertSame(1000, $converter->convert(1000, $this->channel('USD'), new FeedContext()));
    }

    /**
     * @test
     */
    public function it_returns_the_amount_unchanged_when_the_channel_has_no_base_currency(): void
    {
        $converter = new ContextCurrencyConverter($this->prophesize(CurrencyConverterInterface::class)->reveal());

        self::assertSame(1000, $converter->convert(1000, $this->channel(null), new FeedContext(null, null, 'EUR')));
    }

    private function channel(?string $baseCurrencyCode): ChannelInterface
    {
        $channel = $this->prophesize(ChannelInterface::class);

        if (null === $baseCurrencyCode) {
            $channel->getBaseCurrency()->willReturn(null);
        } else {
            $currency = $this->prophesize(CurrencyInterface::class);
            $currency->getCode()->willReturn($baseCurrencyCode);
            $channel->getBaseCurrency()->willReturn($currency->reveal());
        }

        return $channel->reveal();
    }
}
