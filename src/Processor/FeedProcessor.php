<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Processor;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class FeedProcessor implements FeedProcessorInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly FeedRepositoryInterface $feedRepository,
        private readonly MessageBusInterface $commandBus,
    ) {
        $this->logger = new NullLogger();
    }

    public function process(): void
    {
        $feeds = $this->feedRepository->findEnabled();

        foreach ($feeds as $feed) {
            $this->logger->info(sprintf('Triggering processing for feed "%s" (id: %d)', (string) $feed->getName(), (int) $feed->getId()));
            $this->commandBus->dispatch(new ProcessFeed((int) $feed->getId()));
        }
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
