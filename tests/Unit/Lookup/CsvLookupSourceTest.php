<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Lookup;

use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Lookup\CsvLookupSource;

final class CsvLookupSourceTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_parses_rows_keyed_by_the_header(): void
    {
        $stream = fopen('php://temp', 'w+b');
        self::assertIsResource($stream);
        fwrite($stream, "product_code,gtin,badge\nSKU-1,111,Bestseller\nSKU-2,222,New\n");
        rewind($stream);

        $filesystem = $this->prophesize(FilesystemOperator::class);
        $filesystem->readStream('uploads/badges.csv')->willReturn($stream);

        $source = new CsvLookupSource($filesystem->reveal());
        $rows = iterator_to_array($source->fetch(['path' => 'uploads/badges.csv']), false);

        self::assertSame('csv', $source->getType());
        self::assertSame([
            ['product_code' => 'SKU-1', 'gtin' => '111', 'badge' => 'Bestseller'],
            ['product_code' => 'SKU-2', 'gtin' => '222', 'badge' => 'New'],
        ], $rows);
    }

    /**
     * @test
     */
    public function it_yields_nothing_without_a_path(): void
    {
        $source = new CsvLookupSource($this->prophesize(FilesystemOperator::class)->reveal());

        self::assertSame([], iterator_to_array($source->fetch([]), false));
    }
}
