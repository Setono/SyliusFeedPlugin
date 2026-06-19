<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Writer;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Format\GoogleRssFormat;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Writer\XmlWriter;

/**
 * @covers \Setono\SyliusFeedPlugin\Writer\XmlWriter
 */
final class XmlWriterTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_the_xml_format_family(): void
    {
        self::assertSame('xml', (new XmlWriter())->getFormat());
    }

    /**
     * @test
     */
    public function it_streams_a_well_formed_google_rss_document(): void
    {
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);

        $context = new FeedContext();
        $config = (new GoogleRssFormat())->getConfig()->withFeedMetadata([
            'title' => 'Test feed',
            'link' => 'https://example.com',
            'description' => 'A feed',
        ]);

        $writer = new XmlWriter();
        $writer->open($stream, $context, $config);
        $writer->writePreamble();

        $item = new FeedItem(new \stdClass(), $context);
        $item->set('g:id', 'SKU-1');
        $item->set('g:title', 'Acme & Co <Shoe>');
        $item->set('g:additional_image_link', ['https://example.com/a.jpg', 'https://example.com/b.jpg']);
        $item->set('g:shipping', ['g:country' => 'US', 'g:price' => '0 USD']);
        $item->set('g:absent', null);
        $writer->writeItem($item);

        $writer->writeEpilogue();
        $writer->close();

        rewind($stream);
        $xml = stream_get_contents($stream);
        fclose($stream);
        self::assertIsString($xml);

        // well-formed
        $document = new \DOMDocument();
        self::assertTrue($document->loadXML($xml));

        self::assertStringContainsString('xmlns:g="http://base.google.com/ns/1.0"', $xml);
        self::assertStringContainsString('<rss version="2.0"', $xml);
        self::assertStringContainsString('<channel>', $xml);
        self::assertStringContainsString('<title>Test feed</title>', $xml);
        self::assertStringContainsString('<g:id>SKU-1</g:id>', $xml);

        // special characters are escaped
        self::assertStringContainsString('<g:title>Acme &amp; Co &lt;Shoe&gt;</g:title>', $xml);

        // a list becomes repeated elements
        self::assertSame(2, substr_count($xml, '<g:additional_image_link>'));

        // a map becomes a nested element
        self::assertStringContainsString('<g:shipping><g:country>US</g:country><g:price>0 USD</g:price></g:shipping>', $xml);

        // null values are not emitted
        self::assertStringNotContainsString('g:absent', $xml);
    }
}
