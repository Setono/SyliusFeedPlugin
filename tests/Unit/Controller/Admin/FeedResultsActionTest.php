<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Controller\Admin;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Controller\Admin\FeedResultsAction;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

final class FeedResultsActionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_renders_the_feeds_generated_files(): void
    {
        $feed = new Feed();
        $feed->setCode('google');

        $repository = $this->prophesize(FeedRepositoryInterface::class);
        $repository->find(1)->willReturn($feed);

        $listing = (static function (): \Generator {
            yield new DirectoryAttributes('google/nested'); // skipped — not a file
            yield new FileAttributes('google/web_en_us_usd.xml', 123, null, 1700000000);
        })();

        $filesystem = $this->prophesize(FilesystemOperator::class);
        $filesystem->listContents('google', false)->willReturn(new DirectoryListing($listing));

        $twig = $this->prophesize(Environment::class);
        $twig->render(
            '@SetonoSyliusFeedPlugin/admin/feed/results.html.twig',
            Argument::that(static function (array $context): bool {
                $files = $context['files'];
                if (!$context['feed'] instanceof Feed || !\is_array($files) || 1 !== \count($files)) {
                    return false;
                }

                $first = $files[0];

                return \is_array($first) &&
                    'web_en_us_usd.xml' === ($first['filename'] ?? null) &&
                    123 === ($first['size'] ?? null);
            }),
        )->willReturn('<html>results</html>');

        $response = (new FeedResultsAction($repository->reveal(), $filesystem->reveal(), $twig->reveal()))(1);

        self::assertSame('<html>results</html>', $response->getContent());
    }

    /**
     * @test
     */
    public function it_404s_for_a_missing_feed(): void
    {
        $repository = $this->prophesize(FeedRepositoryInterface::class);
        $repository->find(999)->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        (new FeedResultsAction(
            $repository->reveal(),
            $this->prophesize(FilesystemOperator::class)->reveal(),
            $this->prophesize(Environment::class)->reveal(),
        ))(999);
    }
}
