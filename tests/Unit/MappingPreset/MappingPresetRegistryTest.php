<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\MappingPreset;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetInterface;
use Setono\SyliusFeedPlugin\MappingPreset\MappingPresetRegistry;

/**
 * @covers \Setono\SyliusFeedPlugin\MappingPreset\MappingPresetRegistry
 */
final class MappingPresetRegistryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @param array<string, bool> $supports map of feed type code => supported
     */
    private function preset(string $code, array $supports = []): MappingPresetInterface
    {
        $preset = $this->prophesize(MappingPresetInterface::class);
        $preset->getCode()->willReturn($code);
        foreach ($supports as $feedType => $supported) {
            $preset->supports($feedType)->willReturn($supported);
        }

        return $preset->reveal();
    }

    /**
     * @test
     */
    public function it_registers_and_retrieves_presets_keyed_by_code(): void
    {
        $google = $this->preset('google_shopping');

        $registry = new MappingPresetRegistry([$google]);

        self::assertSame($google, $registry->get('google_shopping'));
        self::assertTrue($registry->has('google_shopping'));
        self::assertFalse($registry->has('meta'));
        self::assertSame(['google_shopping' => $google], $registry->all());
    }

    /**
     * @test
     */
    public function it_returns_only_the_presets_supporting_a_given_feed_type(): void
    {
        $google = $this->preset('google_shopping', ['product_variant' => true]);
        $orderExport = $this->preset('order_export', ['product_variant' => false]);

        $registry = new MappingPresetRegistry([$google, $orderExport]);

        self::assertSame([$google], $registry->forFeedType('product_variant'));
    }

    /**
     * @test
     */
    public function it_throws_when_two_presets_share_a_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new MappingPresetRegistry([$this->preset('google_shopping'), $this->preset('google_shopping')]);
    }
}
