<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Delivery;

interface DeliveryMatcherInterface
{
    /**
     * Whether a delivery target's match selects the context identified by the given dimension codes
     * (§12). Each of channel/locale/currency present (and non-empty) in the match must equal the
     * corresponding context code; an omitted dimension is a wildcard and an empty match matches all.
     *
     * @param array<string, mixed> $match
     */
    public function matches(array $match, ?string $channelCode, ?string $localeCode, ?string $currencyCode): bool;
}
