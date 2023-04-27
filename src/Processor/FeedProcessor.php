<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Processor;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class FeedProcessor implements FeedProcessorInterface
{
    use LoggerAwareTrait;

    private FeedRepositoryInterface $feedRepository;

    private MessageBusInterface $commandBus;

    public function __construct(FeedRepositoryInterface $feedRepository, MessageBusInterface $commandBus)
    {
        $this->feedRepository = $feedRepository;
        $this->commandBus = $commandBus;
        $this->logger = new NullLogger();
    }

    public function process(): void
    {
        $feeds = $this->feedRepository->findReadyToBeProcessed();

        foreach ($feeds as $feed) {
            $this->logger->info(
                sprintf(
                    'Triggering processing for feed "%s" (id: %d)',
                    (string) $feed->getName(),
                    (int) $feed->getId(),
                ),
            );
            $this->commandBus->dispatch(new ProcessFeed((int) $feed->getId()));
        }
    }
}
