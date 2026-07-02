<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MappingPreset;

use Setono\SyliusFeedPlugin\Mapping\FieldMapping;
use Setono\SyliusFeedPlugin\Transformation\MoneyFormat;
use Setono\SyliusFeedPlugin\Transformation\StripTags;

/**
 * Seeds a Partner-ads product feed (§7): the `partner_ads` format plus the default output→source
 * field mappings for the `product_variant` feed type. Partner-ads is a Danish affiliate network,
 * hence the Danish element names (`produktnavn`, `VareURL`, `køn`, …).
 */
final class PartnerAdsMappingPreset implements MappingPresetInterface
{
    public function getCode(): string
    {
        return 'partner_ads';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.mapping_preset.partner_ads';
    }

    public function supports(string $feedType): bool
    {
        return 'product_variant' === $feedType;
    }

    public function getFormat(): string
    {
        return 'partner_ads';
    }

    public function getMapping(): array
    {
        return [
            FieldMapping::field('produktid', 'id'),
            FieldMapping::field('produktnavn', 'title'),
            FieldMapping::field('beskrivelse', 'description')->transform(StripTags::all()),
            FieldMapping::field('nypris', 'channel_price')->transform(MoneyFormat::withCurrency()),
            FieldMapping::field('glpris', 'original_price')
                ->transform(MoneyFormat::withCurrency())
                ->onlyIf('on_sale'),
            FieldMapping::field('VareURL', 'link'),
            FieldMapping::field('BilledURL', 'main_image'),
            // Required by Partner-ads but not derivable from core Sylius → flagged for the admin
            // to complete before the feed can be enabled:
            FieldMapping::field('brand', 'attribute:brand')->requiresInput(),
            FieldMapping::field('køn', 'attribute:gender')->requiresInput(),
        ];
    }
}
