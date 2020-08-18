<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Processor;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class FeedProcessor implements FeedProcessorInterface
{
    use LoggerAwareTrait;

    /** @var FeedRepositoryInterface */
    private $feedRepository;

    /** @var MessageBusInterface */
    private $commandBus;

    public function __construct(FeedRepositoryInterface $feedRepository, MessageBusInterface $commandBus)
    {
        $this->feedRepository = $feedRepository;
        $this->commandBus = $commandBus;
        $this->logger = new NullLogger();
    }

    public function process(): void
    {
        $feeds = $this->feedRepository->findEnabled();

        foreach ($feeds as $feed) {
            $this->logger->info(sprintf('Triggering processing for feed "%s" (id: %s)', $feed->getName(), $feed->getId()));
            $this->commandBus->dispatch(new ProcessFeed((int) $feed->getId()));
        }
    }
}
