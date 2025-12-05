<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Controller\Action\Admin;

use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProcessFeedAction
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly FlashBagInterface|RequestStack $flashBag,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(int $id): RedirectResponse
    {
        $this->commandBus->dispatch(new ProcessFeed($id));

        $this->getFlashBag()->add('success', $this->translator->trans('setono_sylius_feed.feed_generation_triggered'));

        return new RedirectResponse($this->urlGenerator->generate('setono_sylius_feed_admin_feed_show', ['id' => $id]));
    }

    private function getFlashBag(): FlashBagInterface
    {
        if ($this->flashBag instanceof FlashBagInterface) {
            return $this->flashBag;
        }

        $session = $this->flashBag->getSession();
        if ($session instanceof Session) {
            return $session->getFlashBag();
        }

        throw new \RuntimeException('Could not get flash bag');
    }
}
