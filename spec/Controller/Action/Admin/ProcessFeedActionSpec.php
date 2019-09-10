<?php

namespace spec\Setono\SyliusFeedPlugin\Controller\Action\Admin;

use PhpSpec\ObjectBehavior;
use Setono\SyliusFeedPlugin\Controller\Action\Admin\ProcessFeedAction;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use stdClass;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProcessFeedActionSpec extends ObjectBehavior
{
    public function let(
        MessageBusInterface $commandBus,
        UrlGeneratorInterface $urlGenerator,
        FlashBagInterface $flashBag,
        TranslatorInterface $translator
    ): void {
        $this->beConstructedWith($commandBus, $urlGenerator, $flashBag, $translator);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ProcessFeedAction::class);
    }

    public function it_dispatches_and_returns_redirect_response(MessageBusInterface $commandBus, UrlGeneratorInterface $urlGenerator): void
    {
        $commandBus->dispatch(new ProcessFeed(1))->willReturn(new Envelope(new stdClass()));
        $urlGenerator->generate('setono_sylius_feed_admin_feed_show', ['id' => 1])->willReturn('path');

        $this->__invoke(1)->shouldReturnAnInstanceOf(RedirectResponse::class);
    }
}
