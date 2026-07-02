<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;

/**
 * Looks the (string-cast) value up in a fixed map, e.g. mapping an internal condition code to
 * Google's vocabulary. Structural: operates on the value as a whole, not element-wise. Unmatched
 * values fall back to `default` when provided, otherwise the original value is returned
 * unchanged (§10).
 */
final class ValueMap implements TransformationInterface
{
    public const TYPE = 'value_map';

    /**
     * @param array<string, mixed> $map
     */
    public static function of(array $map): TransformationConfig
    {
        return new TransformationConfig(self::TYPE, ['map' => $map]);
    }

    /**
     * @param array<string, mixed> $map
     */
    public static function withDefault(array $map, mixed $default): TransformationConfig
    {
        return new TransformationConfig(self::TYPE, ['map' => $map, 'default' => $default]);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(mixed $value, array $params, FeedItem $item): mixed
    {
        $map = $params['map'] ?? null;
        if (!is_array($map)) {
            return $value;
        }

        $key = is_scalar($value) ? (string) $value : '';
        if (array_key_exists($key, $map)) {
            return $map[$key];
        }

        if (array_key_exists('default', $params)) {
            return $params['default'];
        }

        return $value;
    }
}
