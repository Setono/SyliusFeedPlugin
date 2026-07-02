<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Delivery;

use PHPUnit\Framework\TestCase;
use Setono\SyliusFeedPlugin\Delivery\PathTemplateRenderer;

final class PathTemplateRendererTest extends TestCase
{
    private PathTemplateRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new PathTemplateRenderer();
    }

    /**
     * @test
     */
    public function it_replaces_every_placeholder(): void
    {
        $rendered = $this->renderer->render(
            'feeds/{channel}/{locale}/{currency}/{contextKey}.{ext}',
            'web',
            'en_US',
            'USD',
            'web_en_us_usd',
            'xml',
        );

        self::assertSame('feeds/web/en_US/USD/web_en_us_usd.xml', $rendered);
    }

    /**
     * @test
     */
    public function it_renders_the_part_placeholder(): void
    {
        $rendered = $this->renderer->render(
            'feeds/{contextKey}-{part}.{ext}',
            'web',
            'en_US',
            'USD',
            'web_en_us_usd',
            'xml',
            '2',
        );

        self::assertSame('feeds/web_en_us_usd-2.xml', $rendered);
    }

    /**
     * @test
     */
    public function it_renders_absent_dimensions_and_part_as_empty_strings(): void
    {
        $rendered = $this->renderer->render(
            'feeds/{channel}{locale}{currency}{contextKey}{part}.{ext}',
            null,
            null,
            null,
            'default',
            'csv',
        );

        self::assertSame('feeds/default.csv', $rendered);
    }
}
