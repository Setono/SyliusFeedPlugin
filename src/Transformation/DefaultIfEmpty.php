<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;
use Setono\SyliusFeedPlugin\Reference\ReferenceResolverInterface;

/**
 * Falls back to a resolved reference when the value is empty (null, `''` or `[]`). Structural:
 * operates on the value as a whole, not element-wise (§10).
 */
final class DefaultIfEmpty implements TransformationInterface
{
    public const TYPE = 'default_if_empty';

    public function __construct(private readonly ReferenceResolverInterface $referenceResolver)
    {
    }

    public static function of(string $reference): TransformationConfig
    {
        return new TransformationConfig(self::TYPE, ['value' => $reference]);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(mixed $value, array $params, FeedItem $item): mixed
    {
        if (!$this->isEmpty($value)) {
            return $value;
        }

        $reference = $params['value'] ?? null;
        if (!is_string($reference)) {
            return $value;
        }

        return $this->referenceResolver->resolve($reference, $value, $item);
    }

    private function isEmpty(mixed $value): bool
    {
        return null === $value || '' === $value || [] === $value;
    }
}
