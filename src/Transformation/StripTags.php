<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;

/**
 * Strips HTML tags from a string, optionally keeping an allow-list. Maps element-wise over a
 * list; non-string values pass through unchanged (§10).
 */
final class StripTags implements TransformationInterface
{
    public const TYPE = 'strip_tags';

    public static function all(): TransformationConfig
    {
        return new TransformationConfig(self::TYPE);
    }

    public static function allowed(string ...$tags): TransformationConfig
    {
        return new TransformationConfig(self::TYPE, ['allowed' => array_values($tags)]);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(mixed $value, array $params, FeedItem $item): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $element): mixed => $this->strip($element, $params), $value);
        }

        return $this->strip($value, $params);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function strip(mixed $value, array $params): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $allowed = $params['allowed'] ?? [];
        if (is_array($allowed) && [] !== $allowed) {
            return strip_tags($value, array_values(array_filter($allowed, is_string(...))));
        }

        return strip_tags($value);
    }
}
