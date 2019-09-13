<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\EventListener;

use Setono\SyliusFeedPlugin\Event\BatchGeneratedEvent;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class IncrementFinishedBatchesSubscriber implements EventSubscriberInterface
{
    /** @var FeedRepositoryInterface */
    private $feedRepository;

    public function __construct(FeedRepositoryInterface $feedRepository)
    {
        $this->feedRepository = $feedRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BatchGeneratedEvent::class => ['increment', 100], // todo explain why we need a priority
        ];
    }

    public function increment(BatchGeneratedEvent $event): void
    {
        $feed = $event->getFeed();

        $this->feedRepository->incrementFinishedBatches($feed);
    }
}
