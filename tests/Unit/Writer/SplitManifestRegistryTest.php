<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Writer;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Writer\NoneSplitManifest;
use Setono\SyliusFeedPlugin\Writer\SplitManifestRegistry;
use Setono\SyliusFeedPlugin\Writer\SupplementalSplitManifest;

final class SplitManifestRegistryTest extends TestCase
{
    /**
     * @test
     */
    public function it_registers_and_retrieves_strategies_keyed_by_type(): void
    {
        $none = new NoneSplitManifest();
        $supplemental = new SupplementalSplitManifest();

        $registry = new SplitManifestRegistry([$none, $supplemental]);

        self::assertSame($none, $registry->get('none'));
        self::assertSame($supplemental, $registry->get('supplemental'));
        self::assertTrue($registry->has('none'));
        self::assertFalse($registry->has('unknown'));
        self::assertSame(['none' => $none, 'supplemental' => $supplemental], $registry->all());
    }

    /**
     * @test
     */
    public function it_throws_when_two_strategies_share_a_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SplitManifestRegistry([new NoneSplitManifest(), new NoneSplitManifest()]);
    }
}
