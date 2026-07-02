<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\MappingPreset;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Mapping\FieldMapping;
use Setono\SyliusFeedPlugin\MappingPreset\TikTokMappingPreset;

final class TikTokMappingPresetTest extends TestCase
{
    private TikTokMappingPreset $preset;

    protected function setUp(): void
    {
        $this->preset = new TikTokMappingPreset();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('tiktok', $this->preset->getCode());
        self::assertSame('setono_sylius_feed.mapping_preset.tiktok', $this->preset->getLabel());
        self::assertSame('csv', $this->preset->getFormat());
        self::assertTrue($this->preset->supports('product_variant'));
        self::assertFalse($this->preset->supports('order'));
    }

    /**
     * @test
     */
    public function it_maps_the_tiktok_catalog_columns(): void
    {
        $mapping = $this->preset->getMapping();
        self::assertNotEmpty($mapping);

        $outputFields = array_map(static fn (FieldMapping $m): string => $m->getOutputField(), $mapping);

        self::assertSame([
            'sku_id',
            'title',
            'description',
            'availability',
            'price',
            'product_page_url',
            'image_link',
            'brand',
        ], $outputFields);
    }

    /**
     * @test
     */
    public function it_maps_sku_id_and_product_page_url_from_id_and_link(): void
    {
        $byOutput = [];
        foreach ($this->preset->getMapping() as $mapping) {
            $byOutput[$mapping->getOutputField()] = $mapping;
        }

        self::assertSame('id', $byOutput['sku_id']->getSourceValue());
        self::assertSame('link', $byOutput['product_page_url']->getSourceValue());
    }

    /**
     * @test
     */
    public function it_flags_non_core_fields_as_requiring_input(): void
    {
        $byOutput = [];
        foreach ($this->preset->getMapping() as $mapping) {
            $byOutput[$mapping->getOutputField()] = $mapping;
        }

        self::assertTrue($byOutput['brand']->getRequiresInput());
        self::assertFalse($byOutput['sku_id']->getRequiresInput());
    }
}
