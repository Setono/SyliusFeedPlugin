<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\MappingPreset;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Mapping\SourceType;
use Setono\SyliusFeedPlugin\MappingPreset\GoogleShoppingMappingPreset;

final class GoogleShoppingMappingPresetTest extends TestCase
{
    private GoogleShoppingMappingPreset $preset;

    protected function setUp(): void
    {
        $this->preset = new GoogleShoppingMappingPreset();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('google_shopping', $this->preset->getCode());
        self::assertSame('setono_sylius_feed.mapping_preset.google_shopping', $this->preset->getLabel());
        self::assertSame('google_rss', $this->preset->getFormat());
        self::assertTrue($this->preset->supports('product_variant'));
        self::assertFalse($this->preset->supports('order'));
    }

    /**
     * @test
     */
    public function it_maps_the_required_google_fields(): void
    {
        $byOutput = [];
        foreach ($this->preset->getMapping() as $mapping) {
            $byOutput[$mapping->getOutputField()] = $mapping;
        }

        foreach (['g:id', 'g:title', 'g:description', 'g:link', 'g:image_link', 'g:availability', 'g:price'] as $required) {
            self::assertArrayHasKey($required, $byOutput, sprintf('Missing required field "%s"', $required));
        }

        self::assertSame(SourceType::LITERAL, $byOutput['g:condition']->getSourceType());
        self::assertNotNull($byOutput['g:item_group_id']->getCondition());
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

        self::assertTrue($byOutput['g:brand']->getRequiresInput());
        self::assertTrue($byOutput['g:gtin']->getRequiresInput());
        self::assertFalse($byOutput['g:id']->getRequiresInput());
    }
}
