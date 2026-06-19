<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MessageHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Generator\FeedGeneratorInterface;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeedContext;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Workflow\Registry;

/**
 * Generates one context's feed file to temporary storage, then atomically counts it as completed;
 * the handler that finishes the last context completes the feed (§6.3). Because the generator
 * clears the entity manager while streaming, the completion bookkeeping re-fetches the feed.
 */
#[AsMessageHandler(bus: 'setono_sylius_feed.command_bus')]
final class GenerateFeedContextHandler
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly RepositoryInterface $feedRepository,
        private readonly RepositoryInterface $channelRepository,
        private readonly FeedGeneratorInterface $feedGenerator,
        private readonly Registry $workflowRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(GenerateFeedContext $message): void
    {
        $feed = $this->feedRepository->find($message->feedId);
        if (!$feed instanceof FeedInterface) {
            return;
        }

        try {
            $this->feedGenerator->generate($feed, $this->buildContext($message));
        } catch (\Throwable $exception) {
            $this->transition($message->feedId, FeedGraph::TRANSITION_FAIL);

            throw $exception;
        }

        $completed = $this->incrementCompletedContexts($message->feedId);
        $contextCount = $this->contextCount($message->feedId);

        if (null !== $contextCount && $completed >= $contextCount) {
            $this->transition($message->feedId, FeedGraph::TRANSITION_COMPLETE);
        }
    }

    private function buildContext(GenerateFeedContext $message): FeedContext
    {
        $channel = null;
        if (null !== $message->channelCode) {
            $candidate = $this->channelRepository->findOneBy(['code' => $message->channelCode]);
            $channel = $candidate instanceof ChannelInterface ? $candidate : null;
        }

        return new FeedContext($channel, $message->locale, $message->currencyCode);
    }

    /**
     * Atomically increments the completed-context counter and returns the new value, so exactly one
     * handler observes the value that reaches the total even under concurrent processing.
     */
    private function incrementCompletedContexts(int $feedId): int
    {
        $class = $this->feedRepository->getClassName();
        $manager = $this->getManager($class);

        $manager->createQueryBuilder()
            ->update($class, 'f')
            ->set('f.completedContextCount', 'f.completedContextCount + 1')
            ->where('f.id = :id')
            ->setParameter('id', $feedId)
            ->getQuery()
            ->execute();

        return (int) $manager->createQueryBuilder()
            ->select('f.completedContextCount')
            ->from($class, 'f')
            ->where('f.id = :id')
            ->setParameter('id', $feedId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function contextCount(int $feedId): ?int
    {
        $feed = $this->feedRepository->find($feedId);

        return $feed instanceof FeedInterface ? $feed->getContextCount() : null;
    }

    private function transition(int $feedId, string $transition): void
    {
        $feed = $this->feedRepository->find($feedId);

        // Defensive: the feed was loaded moments ago in __invoke, so it cannot be missing here.
        // @codeCoverageIgnoreStart
        if (!$feed instanceof FeedInterface) {
            return;
        }
        // @codeCoverageIgnoreEnd

        $workflow = $this->workflowRegistry->get($feed, FeedGraph::GRAPH);
        if ($workflow->can($feed, $transition)) {
            $workflow->apply($feed, $transition);
            $this->getManager($feed)->flush();
        }
    }
}
