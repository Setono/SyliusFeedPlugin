<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Controller\Admin;

use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Admin "Generate now" action (§13): dispatches {@see ProcessFeed} for the feed and redirects back
 * to the grid with a flash. With an async transport the run is queued; otherwise it runs inline.
 */
final class GenerateFeedAction
{
    public function __construct(
        private readonly FeedRepositoryInterface $feedRepository,
        private readonly MessageBusInterface $commandBus,
        private readonly RouterInterface $router,
    ) {
    }

    public function __invoke(Request $request, int|string $id): Response
    {
        $feed = $this->feedRepository->find($id);
        if (!$feed instanceof FeedInterface) {
            throw new NotFoundHttpException(sprintf('There is no feed with id "%s"', $id));
        }

        $this->commandBus->dispatch(new ProcessFeed($feed));

        $session = $request->getSession();
        if ($session instanceof Session) {
            $session->getFlashBag()->add('success', 'setono_sylius_feed.feed.generation_dispatched');
        }

        return new RedirectResponse($this->router->generate('setono_sylius_feed_admin_feed_index'));
    }
}
