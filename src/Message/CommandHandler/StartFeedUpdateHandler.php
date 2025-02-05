<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\CommandHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeedUpdate;
use Setono\SyliusFeedPlugin\Message\Command\StartFeedUpdate;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedUpdateInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;

final class StartFeedUpdateHandler
{
    use ORMTrait;

    public function __construct(
        private readonly FeedRepositoryInterface $feedRepository,
        /** @var FactoryInterface<FeedUpdateInterface> $feedUpdateFactory */
        private readonly FactoryInterface $feedUpdateFactory,
        private readonly MessageBusInterface $commandBus,
        ManagerRegistry $managerRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(StartFeedUpdate $message): void
    {
        // todo I think we need some logic to make sure that the same feed is only updated in one feed update at a time

        foreach ($this->provideFeeds($message->feeds) as $feed) {
            $feedUpdate = $this->feedUpdateFactory->createNew();
            $feedUpdate->setFeed($feed);

            $manager = $this->getManager($feedUpdate);
            $manager->persist($feedUpdate);
            $manager->flush();

            $this->commandBus->dispatch(new ProcessFeedUpdate($feedUpdate));
        }
    }

    /**
     * @param list<int> $feedIds
     *
     * @return \Generator<array-key, FeedInterface>
     */
    private function provideFeeds(array $feedIds): \Generator
    {
        if ([] === $feedIds) {
            yield from $this->feedRepository->findEnabled();

            return;
        }

        foreach ($feedIds as $feedId) {
            $feed = $this->feedRepository->find($feedId);
            if (null === $feed) {
                throw new UnrecoverableMessageHandlingException(sprintf('Feed with id %d does not exist', $feedId));
            }

            if (!$feed->isEnabled()) {
                continue;
            }

            yield $feed;
        }
    }
}
