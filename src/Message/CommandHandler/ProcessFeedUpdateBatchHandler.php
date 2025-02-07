<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\CommandHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\DataProvider\DataProviderInterface;
use Setono\SyliusFeedPlugin\Generator\FeedPartGeneratorInterface;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeedUpdateBatch;
use Setono\SyliusFeedPlugin\Model\FeedUpdateBatchInterface;
use Setono\SyliusFeedPlugin\Model\FeedUpdateInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedUpdateBatchWorkflow;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Workflow\WorkflowInterface;
use Webmozart\Assert\Assert;

final class ProcessFeedUpdateBatchHandler
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly WorkflowInterface $feedUpdateBatchStateMachine,
        private readonly DataProviderInterface $dataProvider,
        private readonly FeedPartGeneratorInterface $feedPartGenerator,
        /** @var class-string<FeedUpdateBatchInterface> $feedUpdateBatchClass */
        private readonly string $feedUpdateBatchClass,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(ProcessFeedUpdateBatch $message): void
    {
        $manager = $this->getManager($this->feedUpdateBatchClass);

        $feedUpdateBatch = $this->getFeedUpdateBatch($message->feedUpdateBatch);

        $feedUpdate = $feedUpdateBatch->getFeedUpdate();
        if (null === $feedUpdate) {
            throw new UnrecoverableMessageHandlingException(sprintf('Feed update batch with id %d does not have a feed update', $message->feedUpdateBatch));
        }

        $feed = $feedUpdate->getFeed();
        if (null === $feed) {
            throw new UnrecoverableMessageHandlingException(sprintf('Feed update batch with id %d does not have a feed', $message->feedUpdateBatch));
        }

        if ($feedUpdate->getState() === FeedUpdateInterface::STATE_FAILED) {
            // todo transition to failed/cancelled on this particular batch
            return;
        }

        if (!$this->feedUpdateBatchStateMachine->can($feedUpdateBatch, FeedUpdateBatchWorkflow::TRANSITION_PROCESS)) {
            // todo maybe it would be better to check this and if it cannot be processed, transition to failed
            throw new UnrecoverableMessageHandlingException(sprintf('Feed update with id %d cannot be processed', $message->feedUpdateBatch));
        }

        $this->feedUpdateBatchStateMachine->apply($feedUpdateBatch, FeedUpdateBatchWorkflow::TRANSITION_PROCESS);

        $manager->flush();

        try {
            $entity = $feedUpdateBatch->getEntity();
            Assert::notNull($entity);

            $feedUpdateBatch->setPath($this->feedPartGenerator->generate(
                $feed,
                $this->dataProvider->getObjects($entity, $feedUpdateBatch->getIds()),
            ));

            $this->feedUpdateBatchStateMachine->apply($feedUpdateBatch, FeedUpdateBatchWorkflow::TRANSITION_COMPLETE);

            $manager->flush();
        } catch (\Throwable $e) {
            $this->feedUpdateBatchStateMachine->apply($feedUpdateBatch, FeedUpdateBatchWorkflow::TRANSITION_FAIL);

            $manager->flush();

            throw $e;
        }
    }

    private function getFeedUpdateBatch(int $id): FeedUpdateBatchInterface
    {
        $manager = $this->getManager($this->feedUpdateBatchClass);

        $feedUpdateBatch = $manager->find($this->feedUpdateBatchClass, $id);
        if (null === $feedUpdateBatch) {
            throw new UnrecoverableMessageHandlingException(sprintf('Feed update batch with id %d does not exist', $id));
        }

        $manager->refresh($feedUpdateBatch);

        return $feedUpdateBatch;
    }
}
