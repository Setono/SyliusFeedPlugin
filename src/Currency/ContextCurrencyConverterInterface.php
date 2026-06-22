<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Currency;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Sylius\Component\Core\Model\ChannelInterface;

interface ContextCurrencyConverterInterface
{
    /**
     * Converts a channel-base-currency amount (minor units) to the feed context's currency.
     */
    public function convert(int $amount, ChannelInterface $channel, FeedContext $context): int;
}
