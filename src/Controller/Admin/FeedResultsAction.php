<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Controller\Admin;

use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

/**
 * Admin per-context results view (§13): lists the feed's generated files in canonical storage with
 * download links to the public route.
 */
final class FeedResultsAction
{
    public function __construct(
        private readonly FeedRepositoryInterface $feedRepository,
        private readonly FilesystemOperator $feedFilesystem,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(int|string $id): Response
    {
        $feed = $this->feedRepository->find($id);
        if (!$feed instanceof FeedInterface) {
            throw new NotFoundHttpException(sprintf('There is no feed with id "%s"', $id));
        }

        $files = [];
        foreach ($this->feedFilesystem->listContents((string) $feed->getCode(), false) as $item) {
            if (!$item instanceof FileAttributes) {
                continue;
            }

            $files[] = [
                'filename' => basename($item->path()),
                'size' => $item->fileSize(),
                'lastModified' => $item->lastModified(),
            ];
        }

        return new Response($this->twig->render('@SetonoSyliusFeedPlugin/admin/feed/results.html.twig', [
            'feed' => $feed,
            'files' => $files,
        ]));
    }
}
