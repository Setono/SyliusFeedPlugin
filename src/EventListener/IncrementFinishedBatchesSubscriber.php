<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventListener;

use Setono\SyliusFeedPlugin\Event\BatchGeneratedEvent;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class IncrementFinishedBatchesSubscriber implements EventSubscriberInterface
{
    private FeedRepositoryInterface $feedRepository;

    public function __construct(FeedRepositoryInterface $feedRepository)
    {
        $this->feedRepository = $feedRepository;
    }

    public static function getSubscribedEvents(): array
    {
        /**
         * The priority is higher on this subscriber because we want the
         * finished batches count to be present in other subscribers/listeners
         *
         * @see SendFinishGenerationCommandSubscriber for example
         */
        return [
            BatchGeneratedEvent::class => ['increment', 100],
        ];
    }

    public function increment(BatchGeneratedEvent $event): void
    {
        $feed = $event->getFeed();

        $this->feedRepository->incrementFinishedBatches($feed);
    }
}
