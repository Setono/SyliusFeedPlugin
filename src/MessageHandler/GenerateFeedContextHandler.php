<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MessageHandler;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Context\MessageContextFactoryInterface;
use Setono\SyliusFeedPlugin\Format\FormatRegistryInterface;
use Setono\SyliusFeedPlugin\Generator\ChunkPartitionerInterface;
use Setono\SyliusFeedPlugin\Generator\ChunkRange;
use Setono\SyliusFeedPlugin\Generator\FeedContextFinalizerInterface;
use Setono\SyliusFeedPlugin\Generator\FeedGeneratorInterface;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeedChunk;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeedContext;
use Setono\SyliusFeedPlugin\Model\FeedChunkInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Registry;
use Webmozart\Assert\Assert;

/**
 * Generates one context's feed file (§6.3). Small contexts take the inline fast path: generate to
 * staging, then finalize (record → publish gate → count → complete the feed on the last context).
 * A context whose single source exceeds the chunk threshold — and whose output is a single, plain
 * file (no split, no gzip) — is instead fanned out into {@see GenerateFeedChunk} messages that render
 * body-only partials in parallel; the barrier then concatenates them (see
 * {@see \Setono\SyliusFeedPlugin\MessageHandler\FinalizeFeedContextHandler}) into a file that is
 * byte-identical to the inline output. Split/gzip feeds and multi-source feeds always stay inline.
 */
final class GenerateFeedContextHandler
{
    use ORMTrait;

    /**
     * The default source-item count above which a context is fanned out into chunks; overridable per
     * feed via `formatConfig['chunk']['size']`.
     */
    private const DEFAULT_CHUNK_SIZE = 10000;

    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly FeedRepositoryInterface $feedRepository,
        private readonly MessageContextFactoryInterface $contextFactory,
        private readonly FeedGeneratorInterface $feedGenerator,
        private readonly FeedContextFinalizerInterface $feedContextFinalizer,
        private readonly ChunkPartitionerInterface $chunkPartitioner,
        private readonly FormatRegistryInterface $formatRegistry,
        private readonly FactoryInterface $feedChunkFactory,
        private readonly MessageBusInterface $commandBus,
        private readonly Registry $workflowRegistry,
    ) {
        $this->managerRegistry = $managerRegistry;
    }

    public function __invoke(GenerateFeedContext $message): void
    {
        $feed = $this->feedRepository->find($message->feed);
        if (!$feed instanceof FeedInterface) {
            return;
        }

        $context = $this->contextFactory->create($message->channel, $message->locale, $message->currency);

        try {
            if ($this->producesSingleFile($feed)) {
                $ranges = $this->chunkPartitioner->partition($feed, $context, $this->resolveChunkSize($feed));
                if (count($ranges) >= 2) {
                    $this->fanOut($feed, $context, $ranges);

                    return;
                }
            }

            $result = $this->feedGenerator->generate($feed, $context);
        } catch (\Throwable $exception) {
            $this->fail($message->feed);

            throw $exception;
        }

        // The generator clears the entity manager while streaming, so re-fetch a managed feed; it was
        // loaded moments ago, so it is guaranteed to still exist.
        $feed = $this->feedRepository->find($message->feed);
        Assert::isInstanceOf($feed, FeedInterface::class);

        $this->feedContextFinalizer->finalize($feed, $context, $result);
    }

    /**
     * Records one chunk row per range (the barrier's expected set) and dispatches a
     * {@see GenerateFeedChunk} per range. Rows are persisted before any message is dispatched so a
     * synchronously handled chunk always finds its row.
     *
     * @param list<ChunkRange> $ranges
     */
    private function fanOut(FeedInterface $feed, FeedContext $context, array $ranges): void
    {
        $contextKey = $context->key();

        foreach ($ranges as $index => $range) {
            $chunk = $this->feedChunkFactory->createNew();
            Assert::isInstanceOf($chunk, FeedChunkInterface::class);

            $chunk->setFeed($feed);
            $chunk->setContextKey($contextKey);
            $chunk->setChunkIndex($index);
            $chunk->setCompleted(false);

            $this->getManager($chunk)->persist($chunk);
        }

        $this->getManager($feed)->flush();

        foreach ($ranges as $index => $range) {
            $this->commandBus->dispatch(new GenerateFeedChunk(
                $feed,
                $context->getChannel(),
                $context->getLocale(),
                $context->getCurrencyCode(),
                $index,
                $range->start,
                $range->end,
            ));
        }
    }

    /**
     * Fan-out is byte-identical only for a single, un-split, un-gzipped file. A feed that gzips or has
     * any effective split limit stays inline (§6.3, §12) — documented fallback.
     */
    private function producesSingleFile(FeedInterface $feed): bool
    {
        if ((bool) ($feed->getFormatConfig()['gzip'] ?? false)) {
            return false;
        }

        $format = $this->formatRegistry->get((string) $feed->getFormat());
        $limit = $format->getSplitLimit();

        $override = $feed->getFormatConfig()['split'] ?? [];
        if (is_array($override)) {
            foreach (['maxItems', 'maxBytes'] as $key) {
                $value = $override[$key] ?? null;
                if (is_numeric($value)) {
                    $limit[$key] = (int) $value;
                }
            }
        }

        return [] === $limit;
    }

    private function resolveChunkSize(FeedInterface $feed): int
    {
        $chunk = $feed->getFormatConfig()['chunk'] ?? [];
        $size = is_array($chunk) ? ($chunk['size'] ?? null) : null;

        if (is_numeric($size) && (int) $size > 0) {
            return (int) $size;
        }

        return self::DEFAULT_CHUNK_SIZE;
    }

    private function fail(int $feedId): void
    {
        $feed = $this->feedRepository->find($feedId);
        if (!$feed instanceof FeedInterface) {
            return;
        }

        $workflow = $this->workflowRegistry->get($feed, FeedGraph::GRAPH);
        if ($workflow->can($feed, FeedGraph::TRANSITION_FAIL)) {
            $workflow->apply($feed, FeedGraph::TRANSITION_FAIL);
            $this->getManager($feed)->flush();
        }
    }
}
