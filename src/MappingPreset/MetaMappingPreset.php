<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MappingPreset;

use Setono\SyliusFeedPlugin\Mapping\FieldMapping;
use Setono\SyliusFeedPlugin\Transformation\MoneyFormat;
use Setono\SyliusFeedPlugin\Transformation\StripTags;
use Setono\SyliusFeedPlugin\Transformation\Truncate;
use Setono\SyliusFeedPlugin\Transformation\ValueMap;

/**
 * Seeds a Meta (Facebook/Instagram) catalog feed (§7): the `csv` format plus the default
 * output→source field mappings for the `product_variant` feed type. Meta's CSV columns are
 * unprefixed (unlike Google's `g:`-prefixed XML fields) and its availability vocabulary uses
 * spaces rather than underscores.
 */
final class MetaMappingPreset implements MappingPresetInterface
{
    public function getCode(): string
    {
        return 'meta';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.mapping_preset.meta';
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
            FieldMapping::field('id', 'id'),
            FieldMapping::field('title', 'title')->transform(Truncate::chars(150)),
            FieldMapping::field('description', 'description')
                ->transform(StripTags::all())
                ->transform(Truncate::chars(5000)),
            FieldMapping::field('availability', 'availability')->transform(ValueMap::of([
                'in_stock' => 'in stock',
                'out_of_stock' => 'out of stock',
                'preorder' => 'available for order',
                'backorder' => 'available for order',
            ])),
            FieldMapping::literal('condition', 'new'),
            FieldMapping::field('price', 'channel_price')->transform(MoneyFormat::withCurrency()),
            FieldMapping::field('link', 'link'),
            FieldMapping::field('image_link', 'main_image'),
            FieldMapping::field('additional_image_link', 'additional_images'),
            FieldMapping::field('item_group_id', 'item_group_id')->onlyIf('is_configurable'),
            // Required by Meta but not derivable from core Sylius → flagged for the admin to
            // complete before the feed can be enabled:
            FieldMapping::field('brand', 'attribute:brand')->requiresInput(),
        ];
    }
}
