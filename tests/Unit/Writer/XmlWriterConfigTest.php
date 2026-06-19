<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Writer;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Writer\XmlWriterConfig;

/**
 * @covers \Setono\SyliusFeedPlugin\Writer\XmlWriterConfig
 */
final class XmlWriterConfigTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_sensible_defaults(): void
    {
        $config = new XmlWriterConfig();

        self::assertSame('feed', $config->rootElement);
        self::assertSame([], $config->rootAttributes);
        self::assertSame([], $config->namespaces);
        self::assertNull($config->wrapperElement);
        self::assertSame('item', $config->itemElement);
        self::assertSame([], $config->preamble);
    }

    /**
     * @test
     */
    public function it_returns_a_copy_carrying_feed_metadata_and_leaves_the_original_untouched(): void
    {
        $config = new XmlWriterConfig(rootElement: 'rss', wrapperElement: 'channel');

        $withMetadata = $config->withFeedMetadata(['title' => 'Feed', 'link' => 'https://example.com']);

        self::assertNotSame($config, $withMetadata);
        self::assertSame([], $config->preamble, 'the original config is immutable');
        self::assertSame(['title' => 'Feed', 'link' => 'https://example.com'], $withMetadata->preamble);
        // the structural properties are carried over to the copy
        self::assertSame('rss', $withMetadata->rootElement);
        self::assertSame('channel', $withMetadata->wrapperElement);
    }
}
