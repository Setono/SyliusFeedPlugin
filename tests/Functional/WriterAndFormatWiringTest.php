<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional;

use Setono\SyliusFeedPlugin\Format\FormatRegistry;
use Setono\SyliusFeedPlugin\Format\FormatRegistryInterface;
use Setono\SyliusFeedPlugin\Format\GoogleRssFormat;
use Setono\SyliusFeedPlugin\Writer\FeedWriterRegistry;
use Setono\SyliusFeedPlugin\Writer\FeedWriterRegistryInterface;
use Setono\SyliusFeedPlugin\Writer\XmlWriter;

/**
 * Proves the XML writer and the google_rss format are auto-tagged and collected into their
 * registries through the DI prototypes + `_instanceof` autoconfiguration.
 */
final class WriterAndFormatWiringTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_collects_the_xml_writer(): void
    {
        $registry = self::getContainer()->get(FeedWriterRegistry::class);

        self::assertInstanceOf(FeedWriterRegistryInterface::class, $registry);
        self::assertInstanceOf(XmlWriter::class, $registry->get('xml'));
    }

    /**
     * @test
     */
    public function it_collects_the_google_rss_format(): void
    {
        $registry = self::getContainer()->get(FormatRegistry::class);

        self::assertInstanceOf(FormatRegistryInterface::class, $registry);
        self::assertInstanceOf(GoogleRssFormat::class, $registry->get('google_rss'));
    }
}
