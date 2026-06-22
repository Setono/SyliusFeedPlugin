<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Currency;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Currency\Converter\CurrencyConverterInterface;

/**
 * Converts a channel-base-currency amount (minor units) to the feed context's currency (§9.1, §18.6).
 * Channel pricing is stored in the channel's base currency; when a feed fans out over a channel's
 * other enabled currencies, prices must be converted. Returns the amount unchanged when the context
 * has no currency or the channel has no base currency (nothing to convert against); same-currency
 * conversion is an identity in the underlying Sylius converter.
 */
final class ContextCurrencyConverter implements ContextCurrencyConverterInterface
{
    public function __construct(private readonly CurrencyConverterInterface $currencyConverter)
    {
    }

    public function convert(int $amount, ChannelInterface $channel, FeedContext $context): int
    {
        $targetCurrency = $context->getCurrencyCode();
        $baseCurrency = $channel->getBaseCurrency()?->getCode();

        if (null === $targetCurrency || null === $baseCurrency) {
            return $amount;
        }

        return $this->currencyConverter->convert($amount, $baseCurrency, $targetCurrency);
    }
}
