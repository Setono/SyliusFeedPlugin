<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Format;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Format\GoogleRssFormat;
use Setono\SyliusFeedPlugin\Writer\XmlWriterConfig;

final class GoogleRssFormatTest extends TestCase
{
    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        $format = new GoogleRssFormat();

        self::assertSame('google_rss', $format->getCode());
        self::assertSame('xml', $format->getWriter());
        self::assertSame(
            ['g:id', 'g:title', 'g:description', 'g:link', 'g:image_link', 'g:availability', 'g:price'],
            $format->getRequiredFields(),
        );
    }

    /**
     * @test
     */
    public function it_provides_the_rss_structural_config(): void
    {
        $config = (new GoogleRssFormat())->getConfig();

        self::assertInstanceOf(XmlWriterConfig::class, $config);
        self::assertSame('rss', $config->rootElement);
        self::assertSame(['version' => '2.0'], $config->rootAttributes);
        self::assertSame(['g' => 'http://base.google.com/ns/1.0'], $config->namespaces);
        self::assertSame('channel', $config->wrapperElement);
        self::assertSame('item', $config->itemElement);
    }
}
