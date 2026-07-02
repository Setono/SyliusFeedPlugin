<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Controller\Admin;

use Setono\SyliusFeedPlugin\Audit\FeedAuditServiceInterface;
use Setono\SyliusFeedPlugin\Context\ContextFactoryInterface;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Preview\PreviewServiceInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

/**
 * Admin dry-run preview (§11): runs the generator pipeline over a bounded sample of the feed's
 * first context WITHOUT writing anything, then renders the include/exclude funnel, a sample of the
 * mapped output, the excluded items with their drop reasons, and the advisory audit.
 */
final class PreviewFeedAction
{
    public function __construct(
        private readonly FeedRepositoryInterface $feedRepository,
        private readonly ContextFactoryInterface $contextFactory,
        private readonly PreviewServiceInterface $previewService,
        private readonly FeedAuditServiceInterface $auditService,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(int|string $id): Response
    {
        $feed = $this->feedRepository->find($id);
        if (!$feed instanceof FeedInterface) {
            throw new NotFoundHttpException(sprintf('There is no feed with id "%s"', $id));
        }

        $contexts = $this->contextFactory->create($feed);
        $context = $contexts[0] ?? new FeedContext();

        $preview = $this->previewService->preview($feed, $context);
        $audit = $this->auditService->audit($preview);

        $columns = [];
        foreach ($preview->included as $bag) {
            foreach (array_keys($bag) as $key) {
                if (!in_array($key, $columns, true)) {
                    $columns[] = $key;
                }
            }
        }

        return new Response($this->twig->render('@SetonoSyliusFeedPlugin/admin/feed/preview.html.twig', [
            'feed' => $feed,
            'context' => $context,
            'preview' => $preview,
            'audit' => $audit,
            'columns' => $columns,
        ]));
    }
}
