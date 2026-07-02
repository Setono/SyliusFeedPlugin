<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MessageHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\Context\MessageContextFactoryInterface;
use Setono\SyliusFeedPlugin\Generator\ChunkRange;
use Setono\SyliusFeedPlugin\Generator\ChunkRenderResult;
use Setono\SyliusFeedPlugin\Generator\FeedGeneratorInterface;
use Setono\SyliusFeedPlugin\Message\Command\FinalizeFeedContext;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeedChunk;
use Setono\SyliusFeedPlugin\Model\FeedChunkInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Repository\FeedChunkRepositoryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

/**
 * Renders one fan-out chunk (§6.3): builds the chunk's body-only partial for its id range, records
 * the chunk's item/exclusion counts on its row, then atomically flips the row to completed (only once
 * per distinct chunk index) and — when every chunk of the context is completed — dispatches
 * {@see FinalizeFeedContext}.
 *
 * The handler is idempotent, so a Messenger retry is safe: re-running a chunk overwrites its partial,
 * re-writes its counts and no-ops the atomic flip, but still re-checks the barrier — so a chunk that
 * crashed after flipping but before dispatching finalize still triggers finalize on retry. A chunk
 * exception is rethrown (Messenger retries it) WITHOUT failing the feed, so a transient chunk failure
 * never poisons the whole run — the retry completes it and the feed finalizes exactly once.
 */
final class GenerateFeedChunkHandler
{
    use ORMTrait;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly FeedRepositoryInterface $feedRepository,
        private readonly MessageContextFactoryInterface $contextFactory,
        private readonly FeedGeneratorInterface $feedGenerator,
        private readonly FeedChunkRepositoryInterface $feedChunkRepository,
        private readonly MessageBusInterface $commandBus,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(GenerateFeedChunk $message): void
    {
        $feed = $this->feedRepository->find($message->feed);
        if (!$feed instanceof FeedInterface) {
            return;
        }

        $context = $this->contextFactory->create($message->channel, $message->locale, $message->currency);
        $contextKey = $context->key();

        // A thrown chunk propagates so Messenger retries it; the feed is NOT failed, so the retry can
        // still complete the run (chunk-level resumability, §6.3).
        $render = $this->feedGenerator->generateChunk($feed, $context, new ChunkRange($message->start, $message->end), $message->chunkIndex);

        // generateChunk clears the entity manager while streaming, so re-fetch a managed feed.
        $feed = $this->feedRepository->find($message->feed);
        Assert::isInstanceOf($feed, FeedInterface::class);

        $this->recordChunk($feed, $contextKey, $message->chunkIndex, $render);

        // Atomic, idempotent: flips the row false→true only once, so the barrier never double-counts.
        $this->feedChunkRepository->markCompleted($feed, $contextKey, $message->chunkIndex);

        if ($this->feedChunkRepository->allCompleted($feed, $contextKey)) {
            $this->commandBus->dispatch(new FinalizeFeedContext(
                $feed,
                $message->channel,
                $message->locale,
                $message->currency,
            ));
        }
    }

    private function recordChunk(FeedInterface $feed, string $contextKey, int $chunkIndex, ChunkRenderResult $render): void
    {
        $chunk = $this->feedChunkRepository->findOneBy([
            'feed' => $feed,
            'contextKey' => $contextKey,
            'chunkIndex' => $chunkIndex,
        ]);

        if (!$chunk instanceof FeedChunkInterface) {
            return;
        }

        $chunk->setItemCount($render->itemCount);
        $chunk->setExcludedCount($render->excludedCount);
        $chunk->setErrors($render->errors);

        $this->getManager($chunk)->flush();
    }
}
