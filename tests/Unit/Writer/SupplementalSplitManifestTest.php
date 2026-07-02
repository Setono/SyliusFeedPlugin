<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Writer;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Writer\SupplementalSplitManifest;
use Setono\SyliusFeedPlugin\Writer\XmlWriterConfig;

final class SupplementalSplitManifestTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_registered_under_the_supplemental_type(): void
    {
        self::assertSame('supplemental', (new SupplementalSplitManifest())->getType());
    }

    /**
     * @test
     */
    public function it_writes_a_well_formed_manifest_naming_every_part(): void
    {
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);

        (new SupplementalSplitManifest())->writeManifest(
            $stream,
            ['google/web_en_us_usd-1.xml', 'google/web_en_us_usd-2.xml', 'google/web_en_us_usd-3.xml'],
            new FeedContext(null, 'en_US', 'USD'),
            new XmlWriterConfig(),
        );

        rewind($stream);
        $manifest = stream_get_contents($stream);
        self::assertIsString($manifest);
        fclose($stream);

        $document = new \DOMDocument();
        self::assertTrue($document->loadXML($manifest), 'the manifest is well-formed XML');

        // The manifest references each part by its basename (not the full storage path).
        self::assertStringContainsString('<part>web_en_us_usd-1.xml</part>', $manifest);
        self::assertStringContainsString('<part>web_en_us_usd-2.xml</part>', $manifest);
        self::assertStringContainsString('<part>web_en_us_usd-3.xml</part>', $manifest);
        self::assertStringContainsString('parts="3"', $manifest);
        self::assertStringContainsString('context="en_us_usd"', $manifest);
        self::assertStringNotContainsString('google/', $manifest, 'only basenames are referenced');
    }
}
