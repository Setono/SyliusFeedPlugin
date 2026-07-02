<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MappingPreset;

use Setono\SyliusFeedPlugin\Mapping\FieldMapping;
use Setono\SyliusFeedPlugin\Transformation\MoneyFormat;
use Setono\SyliusFeedPlugin\Transformation\StripTags;
use Setono\SyliusFeedPlugin\Transformation\Truncate;

/**
 * Seeds a Google Shopping product feed (§7): the `google_rss` format plus the default output→source
 * field mappings for the `product_variant` feed type. Fields not derivable from core Sylius
 * (brand, gtin) are flagged `requiresInput` for the admin to complete.
 */
final class GoogleShoppingMappingPreset implements MappingPresetInterface
{
    public function getCode(): string
    {
        return 'google_shopping';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.mapping_preset.google_shopping';
    }

    public function supports(string $feedType): bool
    {
        return 'product_variant' === $feedType;
    }

    public function getFormat(): string
    {
        return 'google_rss';
    }

    public function getMapping(): array
    {
        return [
            FieldMapping::field('g:id', 'id'),
            FieldMapping::field('g:item_group_id', 'item_group_id')->onlyIf('is_configurable'),
            FieldMapping::field('g:title', 'title')->transform(Truncate::chars(150)),
            FieldMapping::field('g:description', 'description')
                ->transform(StripTags::all())
                ->transform(Truncate::chars(5000)),
            FieldMapping::field('g:link', 'link'),
            FieldMapping::field('g:image_link', 'main_image'),
            FieldMapping::field('g:additional_image_link', 'additional_images'),
            FieldMapping::field('g:availability', 'availability'),
            FieldMapping::field('g:price', 'channel_price')->transform(MoneyFormat::withCurrency()),
            FieldMapping::literal('g:condition', 'new'),
            // Required/expected by Google but not derivable from core Sylius → flagged for the
            // admin to complete before the feed can be enabled:
            FieldMapping::field('g:brand', 'attribute:brand')->requiresInput(),
            FieldMapping::field('g:gtin', 'attribute:gtin')->requiresInput(),
            FieldMapping::field('g:google_product_category', 'attribute:google_product_category')->requiresInput(),
        ];
    }
}
