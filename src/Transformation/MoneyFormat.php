<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;

/**
 * Formats a minor-unit integer amount as a decimal string and appends a currency code, e.g.
 * `999` → `9.99 USD` (§8.1). The currency comes from `params.currency` or, when absent, the
 * item's context currency. Maps element-wise over a list; non-numeric values pass through (§10).
 */
final class MoneyFormat implements TransformationInterface
{
    public const TYPE = 'money_format';

    public static function withCurrency(int $decimals = 2): TransformationConfig
    {
        return new TransformationConfig(self::TYPE, ['decimals' => $decimals]);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(mixed $value, array $params, FeedItem $item): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $element): mixed => $this->format($element, $params, $item), $value);
        }

        return $this->format($value, $params, $item);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function format(mixed $value, array $params, FeedItem $item): mixed
    {
        if (!is_numeric($value)) {
            return $value;
        }

        $decimals = is_int($params['decimals'] ?? null) ? $params['decimals'] : 2;
        $amount = (float) $value / (10 ** $decimals);
        $formatted = number_format($amount, $decimals, '.', '');

        $currency = $params['currency'] ?? $item->getContext()->getCurrencyCode();
        if (is_string($currency) && '' !== $currency) {
            return $formatted . ' ' . $currency;
        }

        return $formatted;
    }
}
