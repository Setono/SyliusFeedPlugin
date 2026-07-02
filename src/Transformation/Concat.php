<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\TransformationConfig;
use Setono\SyliusFeedPlugin\Reference\ReferenceResolverInterface;

/**
 * Joins a list of resolved references into a single string, e.g. `brand + " " + title` (§10).
 * Structural: operates on the value as a whole, not element-wise. Each part is resolved via the
 * shared reference-resolution rule; parts that resolve to null are dropped so a missing lookup
 * simply adds nothing to the result.
 */
final class Concat implements TransformationInterface
{
    public const TYPE = 'concat';

    public function __construct(private readonly ReferenceResolverInterface $referenceResolver)
    {
    }

    /**
     * @param list<string> $parts
     */
    public static function of(array $parts, string $separator = ''): TransformationConfig
    {
        return new TransformationConfig(self::TYPE, ['parts' => $parts, 'separator' => $separator]);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    public function apply(mixed $value, array $params, FeedItem $item): mixed
    {
        $parts = $params['parts'] ?? null;
        if (!is_array($parts)) {
            return $value;
        }

        $separator = is_string($params['separator'] ?? null) ? $params['separator'] : '';

        $resolved = [];
        foreach ($parts as $part) {
            if (!is_string($part)) {
                continue;
            }

            $resolvedPart = $this->referenceResolver->resolve($part, $value, $item);
            if (null === $resolvedPart) {
                continue;
            }

            $resolved[] = is_scalar($resolvedPart) ? (string) $resolvedPart : '';
        }

        return implode($separator, $resolved);
    }
}
