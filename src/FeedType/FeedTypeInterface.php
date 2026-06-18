<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedType;

use Setono\SyliusFeedPlugin\DataSource\DataSourceInterface;
use Setono\SyliusFeedPlugin\Mapping\FieldDefinition;
use Setono\SyliusFeedPlugin\Mapping\ScopeDimension;

/**
 * A pluggable definition binding a source resource, its scope dimensions, and its available
 * source fields (§5). Owns only the source side — output formats and default mappings live in
 * MappingPresets.
 *
 * Collected into the FeedTypeRegistry via the `setono_sylius_feed.feed_type` tag.
 */
interface FeedTypeInterface
{
    /**
     * e.g. "product_variant", "product", "order".
     */
    public function getCode(): string;

    /**
     * A translation key.
     */
    public function getLabel(): string;

    public function getDataSource(): DataSourceInterface;

    /**
     * @return list<ScopeDimension>
     */
    public function getScopeDimensions(): array;

    /**
     * The source fields; drives the mapping UI.
     *
     * @return array<string, FieldDefinition> keyed by field name
     */
    public function getAvailableFields(): array;
}
