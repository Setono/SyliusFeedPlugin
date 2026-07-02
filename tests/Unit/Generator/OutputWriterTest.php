<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Generator;

use League\Csv\Reader;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Generator\OutputWriter;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Writer\CsvWriter;
use Setono\SyliusFeedPlugin\Writer\CsvWriterConfig;
use Setono\SyliusFeedPlugin\Writer\NoneSplitManifest;
use Setono\SyliusFeedPlugin\Writer\SupplementalSplitManifest;
use Setono\SyliusFeedPlugin\Writer\XmlWriter;
use Setono\SyliusFeedPlugin\Writer\XmlWriterConfig;

/**
 * Focused unit tests for the split/gzip orchestration in {@see OutputWriter}, exercising the byte
 * limit and gzip paths directly (the item-item-count limit and the end-to-end wiring are covered by
 * the FeedGenerator tests).
 */
final class OutputWriterTest extends TestCase
{
    private string $storageDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->storageDir = sys_get_temp_dir() . '/setono-feed-ow-' . bin2hex(random_bytes(6));
        $this->filesystem = new Filesystem(new LocalFilesystemAdapter($this->storageDir));
    }

    protected function tearDown(): void
    {
        $this->filesystem->deleteDirectory('shop');
    }

    /**
     * @test
     */
    public function it_writes_a_single_unsuffixed_file_when_no_limit_is_configured(): void
    {
        $result = (new OutputWriter($this->filesystem))->write(
            $this->items(3),
            new CsvWriter(),
            (new CsvWriterConfig())->withHeader(['id', 'value']),
            new FeedContext(null, 'en_US', 'USD'),
            'shop',
            'ctx',
            'csv',
            [],
            false,
            new NoneSplitManifest(),
        );

        self::assertSame('shop/ctx.csv', $result->primaryPath);
        self::assertSame(['shop/ctx.csv'], $result->paths);
        self::assertSame(3, $result->itemCount);
        self::assertTrue($this->filesystem->fileExists('shop/ctx.csv'));

        $rows = [...Reader::createFromString($this->filesystem->read('shop/ctx.csv'))->getRecords()];
        self::assertCount(4, $rows, 'one header + three item rows in a single file');
    }

    /**
     * @test
     */
    public function it_rotates_on_the_byte_limit(): void
    {
        // Each CSV row is a handful of bytes; a tiny byte cap forces a new part after roughly every
        // row, so several numbered parts are produced.
        $result = (new OutputWriter($this->filesystem))->write(
            $this->items(5),
            new CsvWriter(),
            (new CsvWriterConfig())->withHeader(['id', 'value']),
            new FeedContext(null, 'en_US', 'USD'),
            'shop',
            'ctx',
            'csv',
            ['maxBytes' => 1],
            false,
            new NoneSplitManifest(),
        );

        self::assertSame(5, $result->itemCount);
        self::assertGreaterThan(1, count($result->paths), 'the byte limit forced a split');
        self::assertSame('shop/ctx-1.csv', $result->primaryPath);

        $total = 0;
        foreach ($result->paths as $part) {
            self::assertTrue($this->filesystem->fileExists($part));
            $rows = [...Reader::createFromString($this->filesystem->read($part))->getRecords()];
            self::assertSame(['id', 'value'], $rows[0], 'every part carries its own header row');
            $total += count($rows) - 1;
        }
        self::assertSame(5, $total, 'every item ends up in exactly one part');
    }

    /**
     * @test
     */
    public function it_gzips_every_part_and_the_manifest_when_splitting(): void
    {
        $result = (new OutputWriter($this->filesystem))->write(
            $this->items(2),
            new XmlWriter(),
            new XmlWriterConfig(rootElement: 'feed', itemElement: 'item'),
            new FeedContext(null, 'en_US', 'USD'),
            'shop',
            'ctx',
            'xml',
            ['maxItems' => 1],
            true,
            new SupplementalSplitManifest(),
        );

        self::assertSame([
            'shop/ctx.xml.gz',
            'shop/ctx-1.xml.gz',
            'shop/ctx-2.xml.gz',
        ], $result->paths);
        self::assertSame('shop/ctx.xml.gz', $result->primaryPath);

        foreach (['shop/ctx-1.xml.gz', 'shop/ctx-2.xml.gz'] as $part) {
            $xml = gzdecode($this->filesystem->read($part));
            self::assertIsString($xml);
            self::assertTrue((new \DOMDocument())->loadXML($xml));
        }

        $manifest = gzdecode($this->filesystem->read('shop/ctx.xml.gz'));
        self::assertIsString($manifest);
        // The gzipped manifest references the gzipped part filenames.
        self::assertStringContainsString('<part>ctx-1.xml.gz</part>', $manifest);
        self::assertStringContainsString('<part>ctx-2.xml.gz</part>', $manifest);
    }

    /**
     * @return iterable<FeedItem>
     */
    private function items(int $count): iterable
    {
        $context = new FeedContext(null, 'en_US', 'USD');

        for ($i = 1; $i <= $count; ++$i) {
            $item = new FeedItem((object) [], $context);
            $item->set('id', 'SKU-' . $i);
            $item->set('value', 'row ' . $i);

            yield $item;
        }
    }
}
