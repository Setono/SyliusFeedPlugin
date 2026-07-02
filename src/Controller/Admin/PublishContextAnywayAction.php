<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Controller\Admin;

use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Repository\FeedContextResultRepositoryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Admin "Publish anyway" action (§6.6): overrides the publish gate for one blocked context by
 * promoting its retained staging candidate to canonical storage and marking the latest result as
 * published. Redirects back to the feed's results view with a flash.
 */
final class PublishContextAnywayAction
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly FeedRepositoryInterface $feedRepository,
        private readonly FeedContextResultRepositoryInterface $feedContextResultRepository,
        private readonly FilesystemOperator $feedTmpFilesystem,
        private readonly FilesystemOperator $feedFilesystem,
        private readonly RouterInterface $router,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(Request $request, int|string $id, string $contextKey): Response
    {
        $feed = $this->feedRepository->find($id);
        if (!$feed instanceof FeedInterface) {
            throw new NotFoundHttpException(sprintf('There is no feed with id "%s"', $id));
        }

        $directory = (string) $feed->getCode();

        foreach ($this->feedTmpFilesystem->listContents($directory, true) as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $path = $item->path();
            if (pathinfo($path, \PATHINFO_FILENAME) !== $contextKey) {
                continue;
            }

            $this->feedFilesystem->writeStream($path, $this->feedTmpFilesystem->readStream($path));
            $this->feedTmpFilesystem->delete($path);
        }

        $result = $this->feedContextResultRepository->findLatestForContext($feed, $contextKey);
        if (null !== $result) {
            $result->setPublishState(FeedContextResultInterface::PUBLISH_STATE_PUBLISHED);
            $this->getManager($result)->flush();
        }

        $session = $request->getSession();
        if ($session instanceof Session) {
            $session->getFlashBag()->add('success', 'setono_sylius_feed.feed.context_published');
        }

        return new RedirectResponse(
            $this->router->generate('setono_sylius_feed_admin_feed_results', ['id' => $feed->getId()]),
        );
    }
}
