<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Lookup;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Lookup\UrlLookupSource;

final class UrlLookupSourceTest extends TestCase
{
    /**
     * @test
     */
    public function it_downloads_and_parses_csv_from_a_url(): void
    {
        $url = 'data://text/csv;base64,' . base64_encode("product_code,gtin\nSKU-1,111\n");

        $source = new UrlLookupSource();
        $rows = [...$source->fetch(['url' => $url])];

        self::assertSame('url', $source->getType());
        self::assertSame([['product_code' => 'SKU-1', 'gtin' => '111']], $rows);
    }

    /**
     * @test
     */
    public function it_throws_when_the_download_fails(): void
    {
        $this->expectException(\RuntimeException::class);

        [...(new UrlLookupSource())->fetch(['url' => 'file:///does/not/exist/lookup.csv'])];
    }

    /**
     * @test
     */
    public function it_yields_nothing_without_a_url(): void
    {
        self::assertSame([], [...(new UrlLookupSource())->fetch([])]);
    }
}
