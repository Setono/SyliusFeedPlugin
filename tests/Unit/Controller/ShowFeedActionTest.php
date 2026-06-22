<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Controller;

use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Controller\ShowFeedAction;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ShowFeedActionTest extends TestCase
{
    use ProphecyTrait;

    private function feed(bool $enabled = true): Feed
    {
        $feed = new Feed();
        $feed->setCode('google');
        $feed->setEnabled($enabled);

        return $feed;
    }

    /**
     * @test
     */
    public function it_streams_an_existing_feed_file(): void
    {
        $stream = fopen('php://temp', 'r+');
        self::assertIsResource($stream);
        fwrite($stream, '<rss version="2.0"></rss>');
        rewind($stream);

        $repository = $this->prophesize(FeedRepositoryInterface::class);
        $repository->findOneBy(['code' => 'google'])->willReturn($this->feed());

        $filesystem = $this->prophesize(FilesystemOperator::class);
        $filesystem->fileExists('google/web_en_us_usd.xml')->willReturn(true);
        $filesystem->readStream('google/web_en_us_usd.xml')->willReturn($stream);

        $response = (new ShowFeedAction($repository->reveal(), $filesystem->reveal()))('google', 'web_en_us_usd.xml');

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/xml', $response->headers->get('Content-Type'));

        ob_start();
        $response->sendContent();
        self::assertSame('<rss version="2.0"></rss>', (string) ob_get_clean());
    }

    /**
     * @test
     */
    public function it_returns_404_when_the_feed_does_not_exist(): void
    {
        $repository = $this->prophesize(FeedRepositoryInterface::class);
        $repository->findOneBy(['code' => 'ghost'])->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        (new ShowFeedAction($repository->reveal(), $this->prophesize(FilesystemOperator::class)->reveal()))('ghost', 'web.xml');
    }

    /**
     * @test
     */
    public function it_returns_404_when_the_feed_is_disabled(): void
    {
        $repository = $this->prophesize(FeedRepositoryInterface::class);
        $repository->findOneBy(['code' => 'google'])->willReturn($this->feed(enabled: false));

        $this->expectException(NotFoundHttpException::class);

        (new ShowFeedAction($repository->reveal(), $this->prophesize(FilesystemOperator::class)->reveal()))('google', 'web.xml');
    }

    /**
     * @test
     */
    public function it_returns_404_when_the_file_does_not_exist(): void
    {
        $repository = $this->prophesize(FeedRepositoryInterface::class);
        $repository->findOneBy(['code' => 'google'])->willReturn($this->feed());

        $filesystem = $this->prophesize(FilesystemOperator::class);
        $filesystem->fileExists('google/missing.xml')->willReturn(false);

        $this->expectException(NotFoundHttpException::class);

        (new ShowFeedAction($repository->reveal(), $filesystem->reveal()))('google', 'missing.xml');
    }
}
