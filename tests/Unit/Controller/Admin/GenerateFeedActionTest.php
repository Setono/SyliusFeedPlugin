<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Controller\Admin\GenerateFeedAction;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;

final class GenerateFeedActionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_dispatches_process_feed_and_redirects_to_the_grid(): void
    {
        $feed = new Feed();
        $feed->setCode('google');

        $repository = $this->prophesize(FeedRepositoryInterface::class);
        $repository->find(1)->willReturn($feed);

        $commandBus = $this->prophesize(MessageBusInterface::class);
        $commandBus->dispatch(Argument::type(ProcessFeed::class))->willReturn(new Envelope(new \stdClass()))->shouldBeCalledOnce();

        $router = $this->prophesize(RouterInterface::class);
        $router->generate('setono_sylius_feed_admin_feed_index')->willReturn('/admin/feeds/');

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $response = (new GenerateFeedAction($repository->reveal(), $commandBus->reveal(), $router->reveal()))($request, 1);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/admin/feeds/', $response->getTargetUrl());

        /** @var array<string, list<string>> $flashes */
        $flashes = $session->getFlashBag()->peekAll();
        self::assertContains('setono_sylius_feed.feed.generation_dispatched', $flashes['success'] ?? []);
    }

    /**
     * @test
     */
    public function it_404s_for_a_missing_feed(): void
    {
        $repository = $this->prophesize(FeedRepositoryInterface::class);
        $repository->find(999)->willReturn(null);

        $commandBus = $this->prophesize(MessageBusInterface::class);
        $commandBus->dispatch(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(NotFoundHttpException::class);

        (new GenerateFeedAction($repository->reveal(), $commandBus->reveal(), $this->prophesize(RouterInterface::class)->reveal()))(new Request(), 999);
    }
}
