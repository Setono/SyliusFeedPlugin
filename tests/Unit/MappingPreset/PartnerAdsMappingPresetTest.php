<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\MappingPreset;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Mapping\FieldMapping;
use Setono\SyliusFeedPlugin\MappingPreset\PartnerAdsMappingPreset;

final class PartnerAdsMappingPresetTest extends TestCase
{
    private PartnerAdsMappingPreset $preset;

    protected function setUp(): void
    {
        $this->preset = new PartnerAdsMappingPreset();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('partner_ads', $this->preset->getCode());
        self::assertSame('setono_sylius_feed.mapping_preset.partner_ads', $this->preset->getLabel());
        self::assertSame('partner_ads', $this->preset->getFormat());
        self::assertTrue($this->preset->supports('product_variant'));
        self::assertFalse($this->preset->supports('order'));
    }

    /**
     * @test
     */
    public function it_maps_the_danish_partner_ads_columns(): void
    {
        $mapping = $this->preset->getMapping();
        self::assertNotEmpty($mapping);

        $outputFields = array_map(static fn (FieldMapping $m): string => $m->getOutputField(), $mapping);

        self::assertSame([
            'produktid',
            'produktnavn',
            'beskrivelse',
            'nypris',
            'glpris',
            'VareURL',
            'BilledURL',
            'brand',
            'køn',
        ], $outputFields);
    }

    /**
     * @test
     */
    public function it_only_maps_the_original_price_when_on_sale(): void
    {
        $byOutput = [];
        foreach ($this->preset->getMapping() as $mapping) {
            $byOutput[$mapping->getOutputField()] = $mapping;
        }

        self::assertNotNull($byOutput['glpris']->getCondition());
        self::assertNull($byOutput['nypris']->getCondition());
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
        self::assertTrue($byOutput['køn']->getRequiresInput());
        self::assertFalse($byOutput['produktid']->getRequiresInput());
    }
}
