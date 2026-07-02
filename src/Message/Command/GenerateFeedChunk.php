<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

use Setono\SyliusFeedPlugin\Message\CommandInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;

/**
 * Renders one chunk of a fan-out generation run (§6.3): the source slice `[start, end]` for a
 * context is rendered to a body-only partial, then the per-context chunk counter is advanced; the
 * chunk that completes the last pending chunk dispatches {@see FinalizeFeedContext}. The dimensions
 * are normalized to codes so the message stays transport-safe.
 */
final class GenerateFeedChunk implements CommandInterface
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
        public readonly int $chunkIndex,
        public readonly int $start,
        public readonly int $end,
    ) {
        $this->feed = $feed instanceof FeedInterface ? (int) $feed->getId() : $feed;
        $this->channel = $channel instanceof ChannelInterface ? $channel->getCode() : $channel;
        $this->locale = $locale instanceof LocaleInterface ? $locale->getCode() : $locale;
        $this->currency = $currency instanceof CurrencyInterface ? $currency->getCode() : $currency;
    }
}
