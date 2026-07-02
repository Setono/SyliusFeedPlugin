<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;

/**
 * Formats a numeric value with a fixed number of decimals and configurable separators, optionally
 * appending a currency code. Maps element-wise over a list; non-numeric values pass through
 * unchanged (§10).
 */
final class NumberFormat implements TransformationInterface
{
    public const TYPE = 'number_format';

    public static function decimals(
        int $decimals,
        ?string $decimalSep = null,
        ?string $thousandsSep = null,
        ?string $currency = null,
    ): TransformationConfig {
        $params = ['decimals' => $decimals];

        if (null !== $decimalSep) {
            $params['decimalSep'] = $decimalSep;
        }

        if (null !== $thousandsSep) {
            $params['thousandsSep'] = $thousandsSep;
        }

        if (null !== $currency) {
            $params['currency'] = $currency;
        }

        return new TransformationConfig(self::TYPE, $params);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(mixed $value, array $params, FeedItem $item): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $element): mixed => $this->format($element, $params), $value);
        }

        return $this->format($value, $params);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function format(mixed $value, array $params): mixed
    {
        if (!is_numeric($value)) {
            return $value;
        }

        $decimals = is_int($params['decimals'] ?? null) ? $params['decimals'] : 0;
        $decimalSep = is_string($params['decimalSep'] ?? null) ? $params['decimalSep'] : '.';
        $thousandsSep = is_string($params['thousandsSep'] ?? null) ? $params['thousandsSep'] : ',';

        $formatted = number_format((float) $value, $decimals, $decimalSep, $thousandsSep);

        $currency = $params['currency'] ?? null;
        if (is_string($currency) && '' !== $currency) {
            return $formatted . ' ' . $currency;
        }

        return $formatted;
    }
}
