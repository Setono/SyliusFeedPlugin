<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Controller;

use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Controller\ShowFeedAction;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @covers \Setono\SyliusFeedPlugin\Controller\ShowFeedAction
 */
final class ShowFeedActionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_streams_an_existing_feed_file(): void
    {
        $stream = fopen('php://temp', 'r+');
        self::assertIsResource($stream);
        fwrite($stream, '<rss version="2.0"></rss>');
        rewind($stream);

        $filesystem = $this->prophesize(FilesystemOperator::class);
        $filesystem->fileExists('google/web_en_us_usd.xml')->willReturn(true);
        $filesystem->readStream('google/web_en_us_usd.xml')->willReturn($stream);

        $response = (new ShowFeedAction($filesystem->reveal()))('google', 'web_en_us_usd.xml');

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/xml', $response->headers->get('Content-Type'));

        ob_start();
        $response->sendContent();
        $content = (string) ob_get_clean();

        self::assertSame('<rss version="2.0"></rss>', $content);
    }

    /**
     * @test
     */
    public function it_returns_404_when_the_file_does_not_exist(): void
    {
        $filesystem = $this->prophesize(FilesystemOperator::class);
        $filesystem->fileExists('google/missing.xml')->willReturn(false);

        $this->expectException(NotFoundHttpException::class);

        (new ShowFeedAction($filesystem->reveal()))('google', 'missing.xml');
    }
}
