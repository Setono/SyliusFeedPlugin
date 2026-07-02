<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

use Setono\SyliusFeedPlugin\Message\CommandInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

/**
 * Finalizes a fan-out context once every chunk completed (§6.3): concatenates the ordered body-only
 * partials into the canonical context file, then runs the same finalize the inline path does (record
 * the result, publish gate, count the context, complete the feed on the last one). Re-runnable — the
 * finalize claim makes it a no-op once another finalize consumed the chunks. The dimensions are
 * normalized to codes so the message stays transport-safe.
 */
final class FinalizeFeedContext implements CommandInterface
{
    public readonly int $feed;

    public readonly ?string $channel;

    public readonly ?string $locale;

    public readonly ?string $currency;

    public function __construct(
        int|FeedInterface $feed,
        ChannelInterface|string|null $channel,
        LocaleInterface|string|null $locale,
        CurrencyInterface|string|null $currency,
    ) {
        $this->feed = $feed instanceof FeedInterface ? (int) $feed->getId() : $feed;
        $this->channel = $channel instanceof ChannelInterface ? $channel->getCode() : $channel;
        $this->locale = $locale instanceof LocaleInterface ? $locale->getCode() : $locale;
        $this->currency = $currency instanceof CurrencyInterface ? $currency->getCode() : $currency;
    }
}
