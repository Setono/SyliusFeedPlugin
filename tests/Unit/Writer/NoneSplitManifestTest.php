<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Writer;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Writer\NoneSplitManifest;
use Setono\SyliusFeedPlugin\Writer\XmlWriterConfig;

final class NoneSplitManifestTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_registered_under_the_none_type(): void
    {
        self::assertSame('none', (new NoneSplitManifest())->getType());
    }

    /**
     * @test
     */
    public function it_writes_nothing_because_the_parts_are_self_describing(): void
    {
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);

        (new NoneSplitManifest())->writeManifest(
            $stream,
            ['shop/ctx-1.csv', 'shop/ctx-2.csv'],
            new FeedContext(null, 'en_US', 'USD'),
            new XmlWriterConfig(),
        );

        rewind($stream);
        self::assertSame('', stream_get_contents($stream));

        fclose($stream);
    }
}
