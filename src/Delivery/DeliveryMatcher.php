<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Delivery;

final class DeliveryMatcher implements DeliveryMatcherInterface
{
    public function matches(array $match, ?string $channelCode, ?string $localeCode, ?string $currencyCode): bool
    {
        $codes = [
            'channel' => $channelCode,
            'locale' => $localeCode,
            'currency' => $currencyCode,
        ];

        foreach ($codes as $dimension => $code) {
            $expected = $match[$dimension] ?? null;

            // An omitted (or empty/non-string) dimension is a wildcard.
            if (!is_string($expected) || '' === $expected) {
                continue;
            }

            if ($expected !== $code) {
                return false;
            }
        }

        return true;
    }
}
