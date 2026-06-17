<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Setono\SyliusFeedPlugin\Item\FeedItem;

/**
 * A value-level operation: regex replace, truncate, value-map, prefix, conditional, … (§5, §10).
 *
 * Collected into the TransformationRegistry via the `setono_sylius_feed.transformation` tag.
 */
interface TransformationInterface
{
    /**
     * e.g. "regex_replace".
     */
    public function getType(): string;

    /**
     * @param array<string, mixed> $params
     */
    public function apply(mixed $value, array $params, FeedItem $item): mixed;
}
