<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;

/**
 * Prepends a fixed string. Maps element-wise over a list; non-string values pass through
 * unchanged (§10).
 */
final class Prefix implements TransformationInterface
{
    public const TYPE = 'prefix';

    public static function with(string $text): TransformationConfig
    {
        return new TransformationConfig(self::TYPE, ['text' => $text]);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(mixed $value, array $params, FeedItem $item): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $element): mixed => $this->prepend($element, $params), $value);
        }

        return $this->prepend($value, $params);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function prepend(mixed $value, array $params): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $text = $params['text'] ?? null;

        return is_string($text) ? $text . $value : $value;
    }
}
