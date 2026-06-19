<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Context;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\ContextFactory;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Mapping\ScopeDimension;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSourceInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

/**
 * @covers \Setono\SyliusFeedPlugin\Context\ContextFactory
 */
final class ContextFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @param list<ScopeDimension> $dimensions
     */
    private function factoryForSingleSource(array $dimensions): ContextFactory
    {
        $feedType = $this->prophesize(FeedTypeInterface::class);
        $feedType->getScopeDimensions()->willReturn($dimensions);

        $registry = $this->prophesize(FeedTypeRegistryInterface::class);
        $registry->get('product_variant')->willReturn($feedType->reveal());

        return new ContextFactory($registry->reveal());
    }

    private function source(): FeedSourceInterface
    {
        $source = $this->prophesize(FeedSourceInterface::class);
        $source->getFeedType()->willReturn('product_variant');

        return $source->reveal();
    }

    private function locale(string $code): LocaleInterface
    {
        $locale = $this->prophesize(LocaleInterface::class);
        $locale->getCode()->willReturn($code);

        return $locale->reveal();
    }

    private function currency(string $code): CurrencyInterface
    {
        $currency = $this->prophesize(CurrencyInterface::class);
        $currency->getCode()->willReturn($code);

        return $currency->reveal();
    }

    /**
     * @param list<LocaleInterface> $locales
     * @param list<CurrencyInterface>|null $currencies enabled currencies; defaults to just the base currency
     */
    private function channel(array $locales, ?CurrencyInterface $baseCurrency, ?LocaleInterface $defaultLocale = null, ?array $currencies = null): ChannelInterface
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getLocales()->willReturn(new ArrayCollection($locales));
        $channel->getBaseCurrency()->willReturn($baseCurrency);
        $channel->getCurrencies()->willReturn(new ArrayCollection($currencies ?? array_filter([$baseCurrency])));
        $channel->getDefaultLocale()->willReturn($defaultLocale);

        return $channel->reveal();
    }

    /**
     * @param list<ChannelInterface> $channels
     */
    private function feed(array $channels): FeedInterface
    {
        $feed = $this->prophesize(FeedInterface::class);
        $feed->getSources()->willReturn(new ArrayCollection([$this->source()]));
        $feed->getChannels()->willReturn(new ArrayCollection($channels));

        return $feed->reveal();
    }

    /**
     * @test
     */
    public function it_creates_one_context_per_channel_locale_and_base_currency(): void
    {
        $factory = $this->factoryForSingleSource([ScopeDimension::CHANNEL, ScopeDimension::LOCALE, ScopeDimension::CURRENCY]);
        $channel = $this->channel([$this->locale('en_US')], $this->currency('USD'));

        $contexts = $factory->create($this->feed([$channel]));

        self::assertCount(1, $contexts);
        self::assertSame($channel, $contexts[0]->getChannel());
        self::assertSame('en_US', $contexts[0]->getLocale());
        self::assertSame('USD', $contexts[0]->getCurrencyCode());
    }

    /**
     * @test
     */
    public function it_fans_out_over_each_locale_of_the_channel(): void
    {
        $factory = $this->factoryForSingleSource([ScopeDimension::CHANNEL, ScopeDimension::LOCALE, ScopeDimension::CURRENCY]);
        $channel = $this->channel([$this->locale('en_US'), $this->locale('da_DK')], $this->currency('USD'));

        $contexts = $factory->create($this->feed([$channel]));

        self::assertCount(2, $contexts);
        self::assertSame('en_US', $contexts[0]->getLocale());
        self::assertSame('da_DK', $contexts[1]->getLocale());
    }

    /**
     * @test
     */
    public function it_falls_back_to_the_channel_default_locale_when_no_locales_are_available(): void
    {
        $factory = $this->factoryForSingleSource([ScopeDimension::CHANNEL, ScopeDimension::LOCALE, ScopeDimension::CURRENCY]);
        $channel = $this->channel([], $this->currency('USD'), $this->locale('en_US'));

        $contexts = $factory->create($this->feed([$channel]));

        self::assertCount(1, $contexts);
        self::assertSame('en_US', $contexts[0]->getLocale());
    }

    /**
     * @test
     */
    public function it_fans_out_over_each_enabled_currency_of_the_channel(): void
    {
        $factory = $this->factoryForSingleSource([ScopeDimension::CHANNEL, ScopeDimension::LOCALE, ScopeDimension::CURRENCY]);
        $usd = $this->currency('USD');
        $eur = $this->currency('EUR');
        $channel = $this->channel([$this->locale('en_US')], $usd, null, [$usd, $eur]);

        $contexts = $factory->create($this->feed([$channel]));

        self::assertCount(2, $contexts);
        self::assertSame('USD', $contexts[0]->getCurrencyCode());
        self::assertSame('EUR', $contexts[1]->getCurrencyCode());
    }

    /**
     * @test
     */
    public function it_falls_back_to_the_base_currency_when_the_channel_has_no_currencies(): void
    {
        $factory = $this->factoryForSingleSource([ScopeDimension::CHANNEL, ScopeDimension::LOCALE, ScopeDimension::CURRENCY]);
        $channel = $this->channel([$this->locale('en_US')], $this->currency('USD'), null, []);

        $contexts = $factory->create($this->feed([$channel]));

        self::assertCount(1, $contexts);
        self::assertSame('USD', $contexts[0]->getCurrencyCode());
    }

    /**
     * @test
     */
    public function it_omits_dimensions_the_feed_type_does_not_declare(): void
    {
        $factory = $this->factoryForSingleSource([ScopeDimension::CHANNEL]);
        $channel = $this->channel([$this->locale('en_US')], $this->currency('USD'));

        $contexts = $factory->create($this->feed([$channel]));

        self::assertCount(1, $contexts);
        self::assertSame($channel, $contexts[0]->getChannel());
        self::assertNull($contexts[0]->getLocale());
        self::assertNull($contexts[0]->getCurrencyCode());
    }
}
