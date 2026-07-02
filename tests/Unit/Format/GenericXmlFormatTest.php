<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Format;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Format\GenericXmlFormat;
use Setono\SyliusFeedPlugin\Writer\XmlWriterConfig;

final class GenericXmlFormatTest extends TestCase
{
    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        $format = new GenericXmlFormat();

        self::assertSame('generic_xml', $format->getCode());
        self::assertSame('xml', $format->getWriter());
        self::assertSame([], $format->getRequiredFields());
    }

    /**
     * @test
     */
    public function it_provides_the_xml_structural_config(): void
    {
        $config = (new GenericXmlFormat())->getConfig();

        self::assertInstanceOf(XmlWriterConfig::class, $config);
        self::assertSame('feed', $config->rootElement);
        self::assertSame('item', $config->itemElement);
    }
}
