<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MappingPreset;

use Setono\SyliusFeedPlugin\Mapping\FieldMapping;
use Setono\SyliusFeedPlugin\Transformation\MoneyFormat;
use Setono\SyliusFeedPlugin\Transformation\StripTags;
use Setono\SyliusFeedPlugin\Transformation\Truncate;

/**
 * Seeds a TikTok Shop product feed (§7): the `csv` format plus the default output→source field
 * mappings for the `product_variant` feed type. TikTok uses its own column names (`sku_id`,
 * `product_page_url`) rather than Google's vocabulary.
 */
final class TikTokMappingPreset implements MappingPresetInterface
{
    public function getCode(): string
    {
        return 'tiktok';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.mapping_preset.tiktok';
    }

    public function supports(string $feedType): bool
    {
        return 'product_variant' === $feedType;
    }

    public function getFormat(): string
    {
        return 'csv';
    }

    public function getMapping(): array
    {
        return [
            FieldMapping::field('sku_id', 'id'),
            FieldMapping::field('title', 'title')->transform(Truncate::chars(150)),
            FieldMapping::field('description', 'description')->transform(StripTags::all()),
            FieldMapping::field('availability', 'availability'),
            FieldMapping::field('price', 'channel_price')->transform(MoneyFormat::withCurrency()),
            FieldMapping::field('product_page_url', 'link'),
            FieldMapping::field('image_link', 'main_image'),
            // Required by TikTok but not derivable from core Sylius → flagged for the admin to
            // complete before the feed can be enabled:
            FieldMapping::field('brand', 'attribute:brand')->requiresInput(),
        ];
    }
}
