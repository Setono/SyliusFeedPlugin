<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;

/**
 * Always returns a fixed value, ignoring whatever is currently flowing through the chain.
 * Structural: operates on the value as a whole (§10).
 */
final class LiteralTransformation implements TransformationInterface
{
    public const TYPE = 'literal';

    public static function of(mixed $value): TransformationConfig
    {
        return new TransformationConfig(self::TYPE, ['value' => $value]);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(mixed $value, array $params, FeedItem $item): mixed
    {
        return $params['value'] ?? null;
    }
}
