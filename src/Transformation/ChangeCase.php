<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;

/**
 * Changes the letter case of a string to `upper`, `lower` or `title`. Maps element-wise over a
 * list; non-string values (and unknown case params) pass through unchanged (§10).
 */
final class ChangeCase implements TransformationInterface
{
    public const TYPE = 'change_case';

    public const CASE_UPPER = 'upper';

    public const CASE_LOWER = 'lower';

    public const CASE_TITLE = 'title';

    public static function to(string $case): TransformationConfig
    {
        return new TransformationConfig(self::TYPE, ['case' => $case]);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(mixed $value, array $params, FeedItem $item): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $element): mixed => $this->convert($element, $params), $value);
        }

        return $this->convert($value, $params);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function convert(mixed $value, array $params): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $case = $params['case'] ?? null;

        return match ($case) {
            self::CASE_UPPER => mb_strtoupper($value),
            self::CASE_LOWER => mb_strtolower($value),
            self::CASE_TITLE => mb_convert_case($value, \MB_CASE_TITLE),
            default => $value,
        };
    }
}
