<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Lookup;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Lookup\LookupSourceInterface;
use Setono\SyliusFeedPlugin\Lookup\LookupSourceRegistry;

final class LookupSourceRegistryTest extends TestCase
{
    use ProphecyTrait;

    private function source(string $type): LookupSourceInterface
    {
        $source = $this->prophesize(LookupSourceInterface::class);
        $source->getType()->willReturn($type);

        return $source->reveal();
    }

    /**
     * @test
     */
    public function it_registers_and_retrieves_sources_keyed_by_type(): void
    {
        $csv = $this->source('csv');

        $registry = new LookupSourceRegistry([$csv]);

        self::assertSame($csv, $registry->get('csv'));
        self::assertTrue($registry->has('csv'));
        self::assertFalse($registry->has('url'));
        self::assertSame(['csv' => $csv], $registry->all());
    }

    /**
     * @test
     */
    public function it_throws_when_two_sources_share_a_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new LookupSourceRegistry([$this->source('csv'), $this->source('csv')]);
    }
}
