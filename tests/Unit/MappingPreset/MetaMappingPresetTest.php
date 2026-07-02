<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\MappingPreset;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Mapping\FieldMapping;
use Setono\SyliusFeedPlugin\MappingPreset\MetaMappingPreset;
use Setono\SyliusFeedPlugin\Transformation\ValueMap;

final class MetaMappingPresetTest extends TestCase
{
    private MetaMappingPreset $preset;

    protected function setUp(): void
    {
        $this->preset = new MetaMappingPreset();
    }

    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        self::assertSame('meta', $this->preset->getCode());
        self::assertSame('setono_sylius_feed.mapping_preset.meta', $this->preset->getLabel());
        self::assertSame('csv', $this->preset->getFormat());
        self::assertTrue($this->preset->supports('product_variant'));
        self::assertFalse($this->preset->supports('order'));
    }

    /**
     * @test
     */
    public function it_maps_the_meta_catalog_columns(): void
    {
        $mapping = $this->preset->getMapping();
        self::assertNotEmpty($mapping);

        $outputFields = array_map(static fn (FieldMapping $m): string => $m->getOutputField(), $mapping);

        self::assertSame([
            'id',
            'title',
            'description',
            'availability',
            'condition',
            'price',
            'link',
            'image_link',
            'additional_image_link',
            'item_group_id',
            'brand',
        ], $outputFields);
    }

    /**
     * @test
     */
    public function it_maps_availability_via_a_value_map_using_meta_vocabulary(): void
    {
        $byOutput = [];
        foreach ($this->preset->getMapping() as $mapping) {
            $byOutput[$mapping->getOutputField()] = $mapping;
        }

        $transformations = $byOutput['availability']->getTransformations();
        self::assertNotEmpty($transformations);
        self::assertSame(ValueMap::TYPE, $transformations[0]->getType());
        self::assertSame([
            'in_stock' => 'in stock',
            'out_of_stock' => 'out of stock',
            'preorder' => 'available for order',
            'backorder' => 'available for order',
        ], $transformations[0]->getParams()['map']);
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
        self::assertFalse($byOutput['id']->getRequiresInput());
    }

    /**
     * @test
     */
    public function it_only_maps_item_group_id_for_configurable_items(): void
    {
        $byOutput = [];
        foreach ($this->preset->getMapping() as $mapping) {
            $byOutput[$mapping->getOutputField()] = $mapping;
        }

        self::assertNotNull($byOutput['item_group_id']->getCondition());
    }
}
