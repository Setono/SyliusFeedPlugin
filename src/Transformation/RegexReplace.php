<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;

/**
 * Replaces every match of a regular expression with a replacement string. Maps element-wise over
 * a list; non-string values pass through unchanged. An invalid pattern (or non-string
 * pattern/replacement) is a no-op — the original value is returned unchanged (§10).
 */
final class RegexReplace implements TransformationInterface
{
    public const TYPE = 'regex_replace';

    public static function of(string $pattern, string $replacement, string $flags = ''): TransformationConfig
    {
        return new TransformationConfig(self::TYPE, [
            'pattern' => $pattern,
            'replacement' => $replacement,
            'flags' => $flags,
        ]);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(mixed $value, array $params, FeedItem $item): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $element): mixed => $this->replace($element, $params), $value);
        }

        return $this->replace($value, $params);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function replace(mixed $value, array $params): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $pattern = $params['pattern'] ?? null;
        $replacement = $params['replacement'] ?? null;

        if (!is_string($pattern) || !is_string($replacement)) {
            return $value;
        }

        $flags = is_string($params['flags'] ?? null) ? $params['flags'] : '';
        $regex = '#' . str_replace('#', '\#', $pattern) . '#' . $flags;

        $result = @preg_replace($regex, $replacement, $value);

        return $result ?? $value;
    }
}
