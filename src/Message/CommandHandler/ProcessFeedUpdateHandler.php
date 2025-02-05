<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\CommandHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\DataProvider\DataProviderInterface;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeedUpdate;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeedUpdateBatch;
use Setono\SyliusFeedPlugin\Model\FeedUpdateBatchInterface;
use Setono\SyliusFeedPlugin\Model\FeedUpdateInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedUpdateWorkflow;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final class ProcessFeedUpdateHandler
{
    use ORMTrait;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
        ManagerRegistry $managerRegistry,
        private readonly WorkflowInterface $feedUpdateStateMachine,
        private readonly DataProviderInterface $dataProvider,
        /** @var FactoryInterface<FeedUpdateBatchInterface> $feedUpdateBatchFactory */
        private readonly FactoryInterface $feedUpdateBatchFactory,
        /** @var class-string<FeedUpdateInterface> $feedUpdateClass */
        private readonly string $feedUpdateClass,
        private readonly int $bufferSize = 100,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(ProcessFeedUpdate $message): void
    {
        $manager = $this->getManager($this->feedUpdateClass);

        $feedUpdate = $this->getFeedUpdate($message->feedUpdate);

        $feed = $feedUpdate->getFeed();
        if (null === $feed) {
            throw new UnrecoverableMessageHandlingException(sprintf('Feed update with id %d does not have a feed', $message->feedUpdate));
        }

        if (!$this->feedUpdateStateMachine->can($feedUpdate, FeedUpdateWorkflow::TRANSITION_PROCESS)) {
            throw new UnrecoverableMessageHandlingException(sprintf('Feed update with id %d cannot be processed', $message->feedUpdate));
        }

        $this->feedUpdateStateMachine->apply($feedUpdate, FeedUpdateWorkflow::TRANSITION_PROCESS);

        $manager->flush();

        foreach ($feed->getEntities() as $entity) {
            /**
             * @psalm-suppress MixedArgumentTypeCoercion
             *
             * @var MessageBuffer<int> $buffer
             */
            $buffer = new MessageBuffer(
                $this->bufferSize,
                function (array $ids) use ($message, $entity): void {
                    $feedUpdateBatch = $this->feedUpdateBatchFactory->createNew();
                    $feedUpdateBatch->setFeedUpdate($this->getFeedUpdate($message->feedUpdate));
                    $feedUpdateBatch->setEntity($entity);
                    $feedUpdateBatch->setIds($ids);

                    $manager = $this->getManager($feedUpdateBatch);
                    $manager->persist($feedUpdateBatch);
                    $manager->flush();

                    $this->commandBus->dispatch(new ProcessFeedUpdateBatch($feedUpdateBatch));
                },
            );

            foreach ($this->dataProvider->getIds($entity) as $id) {
                $buffer->push($id);
            }

            $buffer->flush();
        }
    }

    private function getFeedUpdate(int $id): FeedUpdateInterface
    {
        $manager = $this->getManager($this->feedUpdateClass);

        $feedUpdate = $manager->find($this->feedUpdateClass, $id);
        if (null === $feedUpdate) {
            throw new UnrecoverableMessageHandlingException(sprintf('Feed update with id %d does not exist', $id));
        }

        $manager->refresh($feedUpdate);

        return $feedUpdate;
    }
}
