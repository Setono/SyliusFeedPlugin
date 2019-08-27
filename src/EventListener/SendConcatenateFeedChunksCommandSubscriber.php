<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventListener;

use Setono\SyliusFeedPlugin\Event\FeedChunkGeneratedEvent;
use Setono\SyliusFeedPlugin\Message\Command\FinishFeedGeneration;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class SendConcatenateFeedChunksCommandSubscriber implements EventSubscriberInterface
{
    /** @var FeedRepositoryInterface */
    private $feedRepository;

    /** @var MessageBusInterface */
    private $commandBus;

    public function __construct(FeedRepositoryInterface $feedRepository, MessageBusInterface $commandBus)
    {
        $this->feedRepository = $feedRepository;
        $this->commandBus = $commandBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FeedChunkGeneratedEvent::class => 'sendCommand',
        ];
    }

    public function sendCommand(FeedChunkGeneratedEvent $event): void
    {
        $feed = $event->getFeed();

        if (!$this->feedRepository->batchesGenerated($feed)) {
            return;
        }

        $this->commandBus->dispatch(new FinishFeedGeneration($feed->getId()));
    }
}
