<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Controller\Action\Admin;

use Doctrine\Persistence\ObjectManager;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

final class ProcessFeedAction
{
    private MessageBusInterface $commandBus;

    private UrlGeneratorInterface $urlGenerator;

    private FlashBagInterface $flashBag;

    private TranslatorInterface $translator;
    private FeedRepositoryInterface $feedRepository;

    public function __construct(
        MessageBusInterface $commandBus,
        UrlGeneratorInterface $urlGenerator,
        FlashBagInterface $flashBag,
        FeedRepositoryInterface $feedRepository,
        TranslatorInterface $translator
    ) {
        $this->commandBus = $commandBus;
        $this->urlGenerator = $urlGenerator;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
        $this->feedRepository = $feedRepository;
    }

    public function __invoke(int $id): RedirectResponse
    {
        /** @var Feed|null $feed */
        $feed = $this->feedRepository->find($id);

        Assert::notNull($feed);
        Assert::notEq($feed->getState(), FeedGraph::STATE_PROCESSING);

        $this->commandBus->dispatch(new ProcessFeed($id));

        $this->flashBag->add('success', $this->translator->trans('setono_sylius_feed.feed_generation_triggered'));

        return new RedirectResponse($this->urlGenerator->generate('setono_sylius_feed_admin_feed_show', ['id' => $id]));
    }
}
