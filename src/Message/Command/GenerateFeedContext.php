<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

use Setono\SyliusFeedPlugin\Message\CommandInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

/**
 * Generates one context's feed file (to temporary storage) and, when it is the last context of the
 * run, completes the feed. The dimensions accept either entities or scalars; they are normalized to
 * codes so the message stays transport-safe.
 */
final class GenerateFeedContext implements CommandInterface
{
    public readonly int $feed;

    public readonly ?string $channel;

    public readonly ?string $locale;

    public readonly ?string $currency;

    public function __construct(
        int|FeedInterface $feed,
        ChannelInterface|string|null $channel = null,
        LocaleInterface|string|null $locale = null,
        CurrencyInterface|string|null $currency = null,
    ) {
        $this->feed = $feed instanceof FeedInterface ? (int) $feed->getId() : $feed;
        $this->channel = $channel instanceof ChannelInterface ? $channel->getCode() : $channel;
        $this->locale = $locale instanceof LocaleInterface ? $locale->getCode() : $locale;
        $this->currency = $currency instanceof CurrencyInterface ? $currency->getCode() : $currency;
    }
}
