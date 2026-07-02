<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Controller\Admin\RefreshLookupTableAction;
use Setono\SyliusFeedPlugin\Lookup\LookupTableRefresherInterface;
use Setono\SyliusFeedPlugin\Model\LookupTable;
use Setono\SyliusFeedPlugin\Repository\LookupTableRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

final class RefreshLookupTableActionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_refreshes_the_table_and_redirects_to_the_grid(): void
    {
        $table = new LookupTable();
        $table->setCode('badges');

        $repository = $this->prophesize(LookupTableRepositoryInterface::class);
        $repository->find(1)->willReturn($table);

        $refresher = $this->prophesize(LookupTableRefresherInterface::class);
        $refresher->refresh($table)->shouldBeCalledOnce();

        $router = $this->prophesize(RouterInterface::class);
        $router->generate('setono_sylius_feed_admin_lookup_table_index')->willReturn('/admin/lookup-tables/');

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $response = (new RefreshLookupTableAction($repository->reveal(), $refresher->reveal(), $router->reveal()))($request, 1);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/admin/lookup-tables/', $response->getTargetUrl());

        /** @var array<string, list<string>> $flashes */
        $flashes = $session->getFlashBag()->peekAll();
        self::assertContains('setono_sylius_feed.lookup_table.refreshed', $flashes['success'] ?? []);
    }

    /**
     * @test
     */
    public function it_404s_for_a_missing_table(): void
    {
        $repository = $this->prophesize(LookupTableRepositoryInterface::class);
        $repository->find(999)->willReturn(null);

        $refresher = $this->prophesize(LookupTableRefresherInterface::class);
        $refresher->refresh(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(NotFoundHttpException::class);

        (new RefreshLookupTableAction($repository->reveal(), $refresher->reveal(), $this->prophesize(RouterInterface::class)->reveal()))(new Request(), 999);
    }
}
