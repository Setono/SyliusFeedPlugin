<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MessageHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\Context\MessageContextFactoryInterface;
use Setono\SyliusFeedPlugin\Generator\FeedContextFinalizerInterface;
use Setono\SyliusFeedPlugin\Generator\FeedGeneratorInterface;
use Setono\SyliusFeedPlugin\Generator\GenerationResult;
use Setono\SyliusFeedPlugin\Message\Command\FinalizeFeedContext;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Repository\FeedChunkRepositoryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Webmozart\Assert\Assert;

/**
 * Finalizes a fanned-out context once every chunk completed (§6.3). It sums the per-chunk
 * item/exclusion counts, atomically claims the finalize (deleting the context's chunk rows — only the
 * finalize that deletes them proceeds, so it runs exactly once even under retries/parallelism),
 * concatenates the ordered body-only partials into the canonical context file (byte-identical to the
 * inline output), and then runs the shared finalize (record → publish gate → count → complete). A
 * re-run after the claim finds no chunk rows and is a no-op.
 */
final class FinalizeFeedContextHandler
{
    use ORMTrait;

    /**
     * The number of exclusion samples kept when merging the chunks' bounded samples, mirroring
     * {@see \Setono\SyliusFeedPlugin\Generator\ExclusionCollector}.
     */
    private const MAX_ERRORS = 1000;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly FeedRepositoryInterface $feedRepository,
        private readonly MessageContextFactoryInterface $contextFactory,
        private readonly FeedGeneratorInterface $feedGenerator,
        private readonly FeedChunkRepositoryInterface $feedChunkRepository,
        private readonly FeedContextFinalizerInterface $feedContextFinalizer,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(FinalizeFeedContext $message): void
    {
        $feed = $this->feedRepository->find($message->feed);
        if (!$feed instanceof FeedInterface) {
            return;
        }

        $context = $this->contextFactory->create($message->channel, $message->locale, $message->currency);
        $contextKey = $context->key();

        $chunks = $this->feedChunkRepository->findForContext($feed, $contextKey);
        if ([] === $chunks) {
            // Already finalized (the chunk rows were consumed) — nothing to do.
            return;
        }

        $chunkCount = count($chunks);

        $itemCount = 0;
        $excludedCount = 0;
        $errors = [];
        foreach ($chunks as $chunk) {
            $itemCount += $chunk->getItemCount();
            $excludedCount += $chunk->getExcludedCount();
            foreach ($chunk->getErrors() as $error) {
                if (count($errors) < self::MAX_ERRORS) {
                    $errors[] = $error;
                }
            }
        }

        // Exactly-once claim: the finalize that deletes the chunk rows proceeds; a concurrent/retried
        // finalize finds them gone and returns above.
        if (0 === $this->feedChunkRepository->deleteForContext($feed, $contextKey)) {
            return;
        }

        $output = $this->feedGenerator->finalizeChunks($feed, $context, $chunkCount);

        $result = new GenerationResult(
            $output->primaryPath,
            $itemCount,
            $excludedCount,
            $output->bytes,
            $errors,
            $output->paths,
        );

        // The DQL delete/finalize does not clear the manager, but re-fetch defensively so the shared
        // finalize always works against a managed feed.
        $feed = $this->feedRepository->find($message->feed);
        Assert::isInstanceOf($feed, FeedInterface::class);

        $this->feedContextFinalizer->finalize($feed, $context, $result);
    }
}
