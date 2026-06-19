<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;

/**
 * Truncates a string to a maximum length, optionally appending an ellipsis. Maps element-wise
 * over a list; non-string values pass through unchanged (§10).
 */
final class Truncate implements TransformationInterface
{
    public const TYPE = 'truncate';

    public static function chars(int $max, string $ellipsis = ''): TransformationConfig
    {
        return new TransformationConfig(self::TYPE, ['max' => $max, 'ellipsis' => $ellipsis]);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(mixed $value, array $params, FeedItem $item): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $element): mixed => $this->truncate($element, $params), $value);
        }

        return $this->truncate($value, $params);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function truncate(mixed $value, array $params): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $max = $params['max'] ?? null;
        if (!is_int($max) || mb_strlen($value) <= $max) {
            return $value;
        }

        $ellipsis = is_string($params['ellipsis'] ?? null) ? $params['ellipsis'] : '';
        $keep = max(0, $max - mb_strlen($ellipsis));

        return rtrim(mb_substr($value, 0, $keep)) . $ellipsis;
    }
}
