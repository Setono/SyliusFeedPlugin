<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Controller\Admin;

use Setono\SyliusFeedPlugin\Lookup\LookupTableRefresherInterface;
use Setono\SyliusFeedPlugin\Model\LookupTableInterface;
use Setono\SyliusFeedPlugin\Repository\LookupTableRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Admin "Refresh" action (§10.1): re-imports a LookupTable's rows from its source and redirects back
 * to the grid with a flash. A failed import keeps the last-good rows (handled by the refresher).
 */
final class RefreshLookupTableAction
{
    public function __construct(
        private readonly LookupTableRepositoryInterface $repository,
        private readonly LookupTableRefresherInterface $refresher,
        private readonly RouterInterface $router,
    ) {
    }

    public function __invoke(Request $request, int|string $id): Response
    {
        $table = $this->repository->find($id);
        if (!$table instanceof LookupTableInterface) {
            throw new NotFoundHttpException(sprintf('There is no lookup table with id "%s"', $id));
        }

        $this->refresher->refresh($table);

        $session = $request->getSession();
        if ($session instanceof Session) {
            $session->getFlashBag()->add('success', 'setono_sylius_feed.lookup_table.refreshed');
        }

        return new RedirectResponse($this->router->generate('setono_sylius_feed_admin_lookup_table_index'));
    }
}
