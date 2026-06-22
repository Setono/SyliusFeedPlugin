<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Context;

use Setono\SyliusFeedPlugin\FeedType\FeedTypeRegistryInterface;
use Setono\SyliusFeedPlugin\Mapping\ScopeDimension;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Core\Model\ChannelInterface;

/**
 * Expands a feed into contexts as the cartesian product over the union of its sources' scope
 * dimensions (§6.2). Locales and currencies are derived from each channel: the currency dimension
 * fans out over every currency enabled on the channel (prices are FX-converted per context by the
 * value resolvers, §18.6).
 */
final class ContextFactory implements ContextFactoryInterface
{
    public function __construct(private readonly FeedTypeRegistryInterface $feedTypeRegistry)
    {
    }

    public function create(FeedInterface $feed): array
    {
        $dimensions = $this->scopeDimensions($feed);
        $usesChannel = in_array(ScopeDimension::CHANNEL, $dimensions, true);
        $usesLocale = in_array(ScopeDimension::LOCALE, $dimensions, true);
        $usesCurrency = in_array(ScopeDimension::CURRENCY, $dimensions, true);

        /** @var list<ChannelInterface|null> $channels */
        $channels = $usesChannel ? array_values($feed->getChannels()->toArray()) : [null];

        $contexts = [];
        foreach ($channels as $channel) {
            $locales = $usesLocale && null !== $channel ? $this->localeCodes($channel) : [null];

            foreach ($locales as $locale) {
                $currencies = $usesCurrency && null !== $channel ? $this->currencyCodes($channel) : [null];

                foreach ($currencies as $currency) {
                    $contexts[] = new FeedContext($channel, $locale, $currency);
                }
            }
        }

        return $contexts;
    }

    /**
     * @return list<ScopeDimension>
     */
    private function scopeDimensions(FeedInterface $feed): array
    {
        $dimensions = [];

        foreach ($feed->getSources() as $source) {
            $feedType = $this->feedTypeRegistry->get((string) $source->getFeedType());

            foreach ($feedType->getScopeDimensions() as $dimension) {
                if (!in_array($dimension, $dimensions, true)) {
                    $dimensions[] = $dimension;
                }
            }
        }

        return $dimensions;
    }

    /**
     * @return list<string|null>
     */
    private function localeCodes(ChannelInterface $channel): array
    {
        $codes = [];
        foreach ($channel->getLocales() as $locale) {
            if (null !== $locale->getCode()) {
                $codes[] = $locale->getCode();
            }
        }

        if ([] === $codes) {
            return [$channel->getDefaultLocale()?->getCode()];
        }

        return $codes;
    }

    /**
     * @return list<string|null>
     */
    private function currencyCodes(ChannelInterface $channel): array
    {
        $codes = [];
        foreach ($channel->getCurrencies() as $currency) {
            if (null !== $currency->getCode()) {
                $codes[] = $currency->getCode();
            }
        }

        if ([] === $codes) {
            return [$channel->getBaseCurrency()?->getCode()];
        }

        return $codes;
    }
}
