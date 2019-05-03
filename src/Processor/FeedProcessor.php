<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Processor;

use Setono\SyliusFeedPlugin\Message\Command\GenerateFeed;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class FeedProcessor implements FeedProcessorInterface
{
    /**
     * @var FeedRepositoryInterface
     */
    private $feedRepository;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    public function __construct(FeedRepositoryInterface $feedRepository, MessageBusInterface $messageBus)
    {
        $this->feedRepository = $feedRepository;
        $this->messageBus = $messageBus;
    }

    public function process(): void
    {
        $feeds = $this->feedRepository->findEnabled();

        foreach ($feeds as $feed) {
            $this->messageBus->dispatch(new GenerateFeed($feed->getId()));
        }
    }
}
