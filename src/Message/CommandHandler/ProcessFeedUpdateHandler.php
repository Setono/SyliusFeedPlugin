<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\CommandHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeedUpdate;
use Setono\SyliusFeedPlugin\Model\FeedUpdateInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedUpdateWorkflow;
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
        /** @var class-string<FeedUpdateInterface> $feedUpdateClass */
        private readonly string $feedUpdateClass,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(ProcessFeedUpdate $message): void
    {
        $manager = $this->getManager($this->feedUpdateClass);

        $feedUpdate = $manager->find($this->feedUpdateClass, $message->feedUpdate);
        if (null === $feedUpdate) {
            throw new UnrecoverableMessageHandlingException(sprintf('Feed update with id %d does not exist', $message->feedUpdate));
        }

        if(!$this->feedUpdateStateMachine->can($feedUpdate, FeedUpdateWorkflow::TRANSITION_PROCESS)) {
            throw new UnrecoverableMessageHandlingException(sprintf('Feed update with id %d cannot be processed', $message->feedUpdate));
        }

        $this->feedUpdateStateMachine->apply($feedUpdate, FeedUpdateWorkflow::TRANSITION_PROCESS);

        $manager->flush();
    }
}
