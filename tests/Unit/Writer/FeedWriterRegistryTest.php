<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Writer;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Writer\FeedWriterInterface;
use Setono\SyliusFeedPlugin\Writer\FeedWriterRegistry;

/**
 * @covers \Setono\SyliusFeedPlugin\Writer\FeedWriterRegistry
 */
final class FeedWriterRegistryTest extends TestCase
{
    use ProphecyTrait;

    private function writer(string $format): FeedWriterInterface
    {
        $writer = $this->prophesize(FeedWriterInterface::class);
        $writer->getFormat()->willReturn($format);

        return $writer->reveal();
    }

    /**
     * @test
     */
    public function it_registers_and_retrieves_writers_keyed_by_format(): void
    {
        $xml = $this->writer('xml');

        $registry = new FeedWriterRegistry([$xml]);

        self::assertSame($xml, $registry->get('xml'));
        self::assertTrue($registry->has('xml'));
        self::assertFalse($registry->has('csv'));
        self::assertSame(['xml' => $xml], $registry->all());
    }

    /**
     * @test
     */
    public function it_throws_when_two_writers_share_a_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new FeedWriterRegistry([$this->writer('xml'), $this->writer('xml')]);
    }
}
