<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Format;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Format\PartnerAdsFormat;
use Setono\SyliusFeedPlugin\Writer\XmlWriterConfig;

final class PartnerAdsFormatTest extends TestCase
{
    /**
     * @test
     */
    public function it_describes_itself(): void
    {
        $format = new PartnerAdsFormat();

        self::assertSame('partner_ads', $format->getCode());
        self::assertSame('xml', $format->getWriter());
        self::assertSame([], $format->getRequiredFields());
    }

    /**
     * @test
     */
    public function it_provides_the_xml_structural_config(): void
    {
        $config = (new PartnerAdsFormat())->getConfig();

        self::assertInstanceOf(XmlWriterConfig::class, $config);
        self::assertSame('produkter', $config->rootElement);
        self::assertSame('produkt', $config->itemElement);
    }
}
