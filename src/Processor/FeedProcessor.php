<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Processor;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeed;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class FeedProcessor implements FeedProcessorInterface
{
    use LoggerAwareTrait;

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
        $this->logger = new NullLogger();
    }

    /**
     * @throws StringsException
     */
    public function process(): void
    {
        $feeds = $this->feedRepository->findEnabled();

        foreach ($feeds as $feed) {
            $this->logger->info(sprintf('Dispatching generate feed command for feed %s (%s)', $feed->getName(), $feed->getId()));
            $this->messageBus->dispatch(new GenerateFeed($feed->getId()));
        }
    }
}
