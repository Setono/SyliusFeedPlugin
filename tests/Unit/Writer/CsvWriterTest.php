<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Writer;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Writer\CsvWriter;
use Setono\SyliusFeedPlugin\Writer\CsvWriterConfig;

final class CsvWriterTest extends TestCase
{
    /**
     * @return resource
     */
    private function stream()
    {
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);

        return $stream;
    }

    private function item(string $id): FeedItem
    {
        $item = new FeedItem(new \stdClass(), new FeedContext());
        $item->set('id', $id);

        return $item;
    }

    /**
     * @test
     */
    public function it_writes_a_header_row_and_one_row_per_item_keyed_by_the_header(): void
    {
        $stream = $this->stream();
        $writer = new CsvWriter();
        $writer->open($stream, new FeedContext(), (new CsvWriterConfig())->withHeader(['id', 'title', 'images']));
        $writer->writePreamble();

        $first = $this->item('SKU-1');
        $first->set('title', 'Acme, Shoe');
        $first->set('images', ['a.jpg', 'b.jpg']);
        $writer->writeItem($first);

        // second item is missing 'title' entirely → empty cell; order still follows the header
        $second = $this->item('SKU-2');
        $second->set('images', 'single.jpg');
        $writer->writeItem($second);

        $writer->writeEpilogue();
        $writer->close();

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        self::assertIsString($csv);

        $lines = array_values(array_filter(explode("\n", trim($csv)), static fn (string $line): bool => '' !== $line));
        self::assertSame('id,title,images', $lines[0]);
        // comma inside a value → league/csv quotes it; a list is joined with a comma and then quoted
        self::assertSame('SKU-1,"Acme, Shoe","a.jpg,b.jpg"', $lines[1]);
        // missing 'title' becomes an empty cell in the right column
        self::assertSame('SKU-2,,single.jpg', $lines[2]);
    }

    /**
     * @test
     */
    public function it_prepends_a_utf8_bom_when_configured(): void
    {
        $stream = $this->stream();
        $writer = new CsvWriter();
        $writer->open($stream, new FeedContext(), new CsvWriterConfig(';', '"', ['id'], true));
        $writer->writePreamble();
        $writer->close();

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        self::assertIsString($csv);

        self::assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }

    /**
     * @test
     */
    public function it_honours_a_custom_delimiter(): void
    {
        $stream = $this->stream();
        $writer = new CsvWriter();
        $writer->open($stream, new FeedContext(), (new CsvWriterConfig(';'))->withHeader(['a', 'b']));
        $writer->writePreamble();

        $item = $this->item('x');
        $item->set('a', '1');
        $item->set('b', '2');
        $writer->writeItem($item);
        $writer->close();

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        self::assertIsString($csv);

        self::assertStringContainsString('a;b', $csv);
        self::assertStringContainsString('1;2', $csv);
    }
}
