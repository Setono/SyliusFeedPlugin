<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Audit\AuditReport;
use Setono\SyliusFeedPlugin\Audit\FeedAuditServiceInterface;
use Setono\SyliusFeedPlugin\Context\ContextFactoryInterface;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Controller\Admin\PreviewFeedAction;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Preview\PreviewFunnel;
use Setono\SyliusFeedPlugin\Preview\PreviewResult;
use Setono\SyliusFeedPlugin\Preview\PreviewServiceInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

final class PreviewFeedActionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_renders_the_preview_and_audit(): void
    {
        $feed = new Feed();
        $feed->setCode('google');

        $repository = $this->prophesize(FeedRepositoryInterface::class);
        $repository->find(1)->willReturn($feed);

        $context = new FeedContext(null, 'en_US', 'USD');
        $contextFactory = $this->prophesize(ContextFactoryInterface::class);
        $contextFactory->create($feed)->willReturn([$context]);

        $preview = new PreviewResult(
            new PreviewFunnel(2, 2, 1, 1, 1),
            [['id' => 'SKU-1', 'title' => 'Shoe']],
            [['item' => null, 'reason' => 'validation:missing']],
        );
        $previewService = $this->prophesize(PreviewServiceInterface::class);
        $previewService->preview($feed, $context)->willReturn($preview);

        $audit = new AuditReport(['id' => 1.0], [], []);
        $auditService = $this->prophesize(FeedAuditServiceInterface::class);
        $auditService->audit($preview)->willReturn($audit);

        $twig = $this->prophesize(Environment::class);
        $twig->render(
            '@SetonoSyliusFeedPlugin/admin/feed/preview.html.twig',
            Argument::that(static fn (array $context): bool => $context['feed'] === $feed &&
                $context['preview'] === $preview &&
                $context['audit'] === $audit &&
                ['id', 'title'] === $context['columns']),
        )->willReturn('<html>preview</html>');

        $response = (new PreviewFeedAction(
            $repository->reveal(),
            $contextFactory->reveal(),
            $previewService->reveal(),
            $auditService->reveal(),
            $twig->reveal(),
        ))(1);

        self::assertSame('<html>preview</html>', $response->getContent());
    }

    /**
     * @test
     */
    public function it_404s_for_a_missing_feed(): void
    {
        $repository = $this->prophesize(FeedRepositoryInterface::class);
        $repository->find(999)->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        (new PreviewFeedAction(
            $repository->reveal(),
            $this->prophesize(ContextFactoryInterface::class)->reveal(),
            $this->prophesize(PreviewServiceInterface::class)->reveal(),
            $this->prophesize(FeedAuditServiceInterface::class)->reveal(),
            $this->prophesize(Environment::class)->reveal(),
        ))(999);
    }
}
