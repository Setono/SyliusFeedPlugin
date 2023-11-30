<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventListener;

use Setono\SyliusFeedPlugin\Event\BatchGeneratedEvent;
use Setono\SyliusFeedPlugin\Message\Command\FinishGeneration;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class SendFinishGenerationCommandSubscriber implements EventSubscriberInterface
{
    private FeedRepositoryInterface $feedRepository;

    private MessageBusInterface $commandBus;

    public function __construct(FeedRepositoryInterface $feedRepository, MessageBusInterface $commandBus)
    {
        $this->feedRepository = $feedRepository;
        $this->commandBus = $commandBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BatchGeneratedEvent::class => 'sendCommand',
        ];
    }

    public function sendCommand(BatchGeneratedEvent $event): void
    {
        $feed = $event->getFeed();

        if (!$this->feedRepository->batchesGenerated($feed)) {
            return;
        }

        $this->commandBus->dispatch(new FinishGeneration((int) $feed->getId()));
    }
}
