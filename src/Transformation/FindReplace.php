<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;

/**
 * Replaces every occurrence of a literal substring with another. Maps element-wise over a list;
 * non-string values (and non-string search/replace params) pass through unchanged (§10).
 */
final class FindReplace implements TransformationInterface
{
    public const TYPE = 'find_replace';

    public static function of(string $search, string $replace): TransformationConfig
    {
        return new TransformationConfig(self::TYPE, ['search' => $search, 'replace' => $replace]);
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

        $search = $params['search'] ?? null;
        $replace = $params['replace'] ?? null;

        if (!is_string($search) || !is_string($replace)) {
            return $value;
        }

        return str_replace($search, $replace, $value);
    }
}
