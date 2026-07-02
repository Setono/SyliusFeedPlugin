<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use League\Csv\Reader;
use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Context\MessageContextFactoryInterface;
use Setono\SyliusFeedPlugin\Generator\ChunkPartitionerInterface;
use Setono\SyliusFeedPlugin\Generator\ChunkRange;
use Setono\SyliusFeedPlugin\Generator\ChunkRenderResult;
use Setono\SyliusFeedPlugin\Generator\FeedGenerator;
use Setono\SyliusFeedPlugin\Generator\FeedGeneratorInterface;
use Setono\SyliusFeedPlugin\Generator\GenerationResult;
use Setono\SyliusFeedPlugin\Generator\OutputResult;
use Setono\SyliusFeedPlugin\Message\Command\FinalizeFeedContext;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeedChunk;
use Setono\SyliusFeedPlugin\MessageHandler\GenerateFeedChunkHandler;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedChunkInterface;
use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSource;
use Setono\SyliusFeedPlugin\Repository\FeedChunkRepositoryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Tests\Functional\FunctionalTestCase;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Currency\Model\Currency;
use Sylius\Component\Locale\Model\Locale;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * M7 fan-out resumability acceptance (§6.3): a mid-run chunk failure does not finalize the context;
 * re-running the failed chunk finalizes it exactly once, and the resulting canonical file is
 * byte-identical to the inline output with no duplicated or missing items.
 */
final class FanOutResumabilityTest extends FunctionalTestCase
{
    protected function tearDown(): void
    {
        foreach (['setono_sylius_feed.storage.feed', 'setono_sylius_feed.storage.feed_tmp'] as $id) {
            $filesystem = self::getContainer()->get($id);
            if ($filesystem instanceof FilesystemOperator) {
                $filesystem->deleteDirectory('catalog');
            }
        }

        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_resumes_after_a_mid_run_chunk_failure_and_finalizes_exactly_once(): void
    {
        $channel = $this->persistFixtures();
        $feed = $this->persistFanOutFeed($channel);
        $feedId = (int) $feed->getId();
        $context = new FeedContext($channel, 'en_US', 'USD');
        $contextKey = $context->key();

        // Plan the ranges the fan-out would use and seed the barrier rows, mirroring the handler's fan-out.
        $partitioner = self::getContainer()->get(ChunkPartitionerInterface::class);
        self::assertInstanceOf(ChunkPartitionerInterface::class, $partitioner);
        $ranges = $partitioner->partition($feed, $context, 1);
        self::assertGreaterThanOrEqual(2, count($ranges), 'the tiny chunk size must force at least two chunks');
        $this->seedFanOut($feed, $contextKey, $ranges);

        // A generator that throws the first time chunk index 1 is rendered (a simulated mid-run failure).
        $generator = $this->throwingGenerator(1);
        $handler = $this->chunkHandler($generator);

        // Chunk 0 succeeds — but the context is not finalized while later chunks are pending.
        $handler(new GenerateFeedChunk($feed, 'web', 'en_US', 'USD', 0, $ranges[0]->start, $ranges[0]->end));
        self::assertSame(FeedGraph::STATE_PROCESSING, $this->reload($feedId)->getState());
        self::assertNull($this->latestResult($feedId, $contextKey), 'not finalized while a chunk is pending');

        // Chunk 1 fails: the context stays un-finalized and its barrier row stays pending.
        try {
            $handler(new GenerateFeedChunk($feed, 'web', 'en_US', 'USD', 1, $ranges[1]->start, $ranges[1]->end));
            self::fail('Expected the chunk to throw');
        } catch (\RuntimeException) {
            // expected — Messenger would retry the chunk
        }

        self::assertSame(FeedGraph::STATE_PROCESSING, $this->reload($feedId)->getState(), 'the feed is not failed by a chunk exception');
        self::assertNull($this->latestResult($feedId, $contextKey), 'a failed chunk does not finalize the context');
        self::assertFalse($this->feedTmp()->fileExists('catalog/' . $contextKey . '.chunk-1.csv'), 'the failed chunk wrote no partial');

        // Re-run every remaining chunk (Messenger retry): the last one finalizes the context exactly once.
        for ($index = 1, $count = count($ranges); $index < $count; ++$index) {
            $handler(new GenerateFeedChunk($feed, 'web', 'en_US', 'USD', $index, $ranges[$index]->start, $ranges[$index]->end));
        }

        $feed = $this->reload($feedId);
        self::assertSame(FeedGraph::STATE_COMPLETED, $feed->getState(), 'the feed finalizes after the retry');
        self::assertSame(1, $feed->getCompletedContextCount(), 'the context is counted exactly once');
        self::assertCount(1, $this->allResults($feedId, $contextKey), 'exactly one context result was recorded');

        // The finalized file was promoted to canonical storage; the partials + barrier rows are gone.
        $canonical = self::getContainer()->get('setono_sylius_feed.storage.feed');
        self::assertInstanceOf(FilesystemOperator::class, $canonical);
        self::assertTrue($canonical->fileExists('catalog/' . $contextKey . '.csv'));
        $fanOutBytes = $canonical->read('catalog/' . $contextKey . '.csv');

        self::assertSame([], $this->chunkRepository()->findForContext($feed, $contextKey), 'barrier rows consumed');

        // Every variant row is present exactly once, in order — no duplicated or missing items.
        $reader = Reader::createFromString($fanOutBytes);
        $reader->setHeaderOffset(0);
        $ids = array_map(self::rowId(...), array_values([...$reader->getRecords()]));
        self::assertSame(['VARIANT-1', 'VARIANT-2', 'VARIANT-3'], $ids, 'each variant appears exactly once, in id order');

        // Byte-identical: an inline generation of the same feed produces the same bytes.
        $inline = self::getContainer()->get(FeedGenerator::class);
        self::assertInstanceOf(FeedGenerator::class, $inline);
        $inlineResult = $inline->generate($feed, $context);
        $inlineBytes = $this->feedTmp()->read($inlineResult->path);
        $this->feedTmp()->delete($inlineResult->path);

        self::assertSame($inlineBytes, $fanOutBytes, 'the fan-out output must be byte-identical to the inline output');

        // A stray retried FinalizeFeedContext is a no-op (the barrier rows were already consumed).
        $bus = self::getContainer()->get('setono_sylius_feed.command_bus');
        self::assertInstanceOf(MessageBusInterface::class, $bus);
        $bus->dispatch(new FinalizeFeedContext($feed, 'web', 'en_US', 'USD'));
        self::assertCount(1, $this->allResults($feedId, $contextKey), 'a re-run finalize does not record a second result');
    }

    /**
     * A {@see FeedGeneratorInterface} decorator that throws the first time the given chunk index is
     * rendered, then delegates — a simulated transient chunk failure that a retry recovers from.
     */
    private function throwingGenerator(int $failChunkIndex): FeedGeneratorInterface
    {
        $inner = self::getContainer()->get(FeedGenerator::class);
        self::assertInstanceOf(FeedGenerator::class, $inner);

        return new class($inner, $failChunkIndex) implements FeedGeneratorInterface {
            private bool $thrown = false;

            public function __construct(
                private readonly FeedGeneratorInterface $inner,
                private readonly int $failChunkIndex,
            ) {
            }

            public function generate(FeedInterface $feed, FeedContext $context): GenerationResult
            {
                return $this->inner->generate($feed, $context);
            }

            public function items(FeedInterface $feed, FeedContext $context, ?ChunkRange $range = null): iterable
            {
                return $this->inner->items($feed, $context, $range);
            }

            public function generateChunk(FeedInterface $feed, FeedContext $context, ChunkRange $range, int $chunkIndex): ChunkRenderResult
            {
                if ($chunkIndex === $this->failChunkIndex && !$this->thrown) {
                    $this->thrown = true;

                    throw new \RuntimeException('simulated chunk failure');
                }

                return $this->inner->generateChunk($feed, $context, $range, $chunkIndex);
            }

            public function finalizeChunks(FeedInterface $feed, FeedContext $context, int $chunkCount): OutputResult
            {
                return $this->inner->finalizeChunks($feed, $context, $chunkCount);
            }
        };
    }

    private function chunkHandler(FeedGeneratorInterface $generator): GenerateFeedChunkHandler
    {
        $contextFactory = self::getContainer()->get(MessageContextFactoryInterface::class);
        self::assertInstanceOf(MessageContextFactoryInterface::class, $contextFactory);

        $bus = self::getContainer()->get('setono_sylius_feed.command_bus');
        self::assertInstanceOf(MessageBusInterface::class, $bus);

        return new GenerateFeedChunkHandler(
            $this->managerRegistry(),
            $this->feedRepository(),
            $contextFactory,
            $generator,
            $this->chunkRepository(),
            $bus,
        );
    }

    /**
     * @param list<ChunkRange> $ranges
     */
    private function seedFanOut(FeedInterface $feed, string $contextKey, array $ranges): void
    {
        $feed->setState(FeedGraph::STATE_PROCESSING);
        $feed->setContextCount(1);
        $feed->setCompletedContextCount(0);

        $factory = self::getContainer()->get('setono_sylius_feed.factory.feed_chunk');
        self::assertInstanceOf(FactoryInterface::class, $factory);

        $manager = $this->entityManager();
        foreach ($ranges as $index => $range) {
            $chunk = $factory->createNew();
            self::assertInstanceOf(FeedChunkInterface::class, $chunk);
            $chunk->setFeed($feed);
            $chunk->setContextKey($contextKey);
            $chunk->setChunkIndex($index);
            $chunk->setCompleted(false);
            $manager->persist($chunk);
        }
        $manager->flush();
    }

    private function latestResult(int $feedId, string $contextKey): ?FeedContextResultInterface
    {
        $results = $this->allResults($feedId, $contextKey);

        return $results[0] ?? null;
    }

    /**
     * @return list<FeedContextResultInterface>
     */
    private function allResults(int $feedId, string $contextKey): array
    {
        $this->entityManager()->clear();

        $repository = self::getContainer()->get('setono_sylius_feed.repository.feed_context_result');
        self::assertInstanceOf(RepositoryInterface::class, $repository);

        $results = [];
        foreach ($repository->findBy(['feed' => $feedId, 'contextKey' => $contextKey]) as $result) {
            self::assertInstanceOf(FeedContextResultInterface::class, $result);
            $results[] = $result;
        }

        return $results;
    }

    private function reload(int $feedId): FeedInterface
    {
        $this->entityManager()->clear();
        $feed = $this->feedRepository()->find($feedId);
        self::assertInstanceOf(FeedInterface::class, $feed);

        return $feed;
    }

    private function feedTmp(): FilesystemOperator
    {
        $filesystem = self::getContainer()->get('setono_sylius_feed.storage.feed_tmp');
        self::assertInstanceOf(FilesystemOperator::class, $filesystem);

        return $filesystem;
    }

    private function chunkRepository(): FeedChunkRepositoryInterface
    {
        $repository = self::getContainer()->get('setono_sylius_feed.repository.feed_chunk');
        self::assertInstanceOf(FeedChunkRepositoryInterface::class, $repository);

        return $repository;
    }

    private function feedRepository(): FeedRepositoryInterface
    {
        $repository = self::getContainer()->get('setono_sylius_feed.repository.feed');
        self::assertInstanceOf(FeedRepositoryInterface::class, $repository);

        return $repository;
    }

    private function managerRegistry(): ManagerRegistry
    {
        $registry = self::getContainer()->get('doctrine');
        self::assertInstanceOf(ManagerRegistry::class, $registry);

        return $registry;
    }

    private function entityManager(): EntityManagerInterface
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
    }

    private function persistFanOutFeed(Channel $channel): FeedInterface
    {
        $feed = new Feed();
        $feed->setCode('catalog');
        $feed->setFormat('csv');
        $feed->setEnabled(true);
        $feed->setCurrentLocale('en_US');
        $feed->setName('Catalog feed');
        $feed->setSlug('catalog-feed');
        // Force fan-out: a chunk size of 1 splits the catalog into one chunk per variant.
        $feed->setFormatConfig(['chunk' => ['size' => 1]]);
        $feed->addChannel($channel);

        $source = new FeedSource();
        $source->setFeedType('product_variant');
        $source->setPosition(0);
        foreach (['id', 'title', 'channel_price'] as $position => $name) {
            $field = new \Setono\SyliusFeedPlugin\Model\FeedField();
            $field->setOutputField($name);
            $field->setSourceType('field');
            $field->setSourceValue($name);
            $field->setPosition($position);
            $source->addField($field);
        }
        $feed->addSource($source);

        $manager = $this->entityManager();
        $manager->persist($feed);
        $manager->flush();

        return $feed;
    }

    private function persistFixtures(): Channel
    {
        $currency = new Currency();
        $currency->setCode('USD');

        $locale = new Locale();
        $locale->setCode('en_US');

        $channel = new Channel();
        $channel->setCode('web');
        $channel->setName('Web');
        $channel->setHostname('localhost');
        $channel->setBaseCurrency($currency);
        $channel->setDefaultLocale($locale);
        $channel->addCurrency($currency);
        $channel->addLocale($locale);
        $channel->setTaxCalculationStrategy('order_items_based');
        $channel->setEnabled(true);

        $manager = $this->entityManager();
        foreach ([$currency, $locale, $channel] as $entity) {
            $manager->persist($entity);
        }

        for ($i = 1; $i <= 3; ++$i) {
            $product = new Product();
            $product->setCode('PROD-' . $i);
            $product->setEnabled(true);
            $product->setCurrentLocale('en_US');
            $product->setFallbackLocale('en_US');
            $product->setName('Product ' . $i);
            $product->setSlug('product-' . $i);
            $product->addChannel($channel);

            $variant = new ProductVariant();
            $variant->setCode('VARIANT-' . $i);
            $variant->setEnabled(true);

            $channelPricing = new ChannelPricing();
            $channelPricing->setChannelCode('web');
            $channelPricing->setPrice(100 * $i);
            $variant->addChannelPricing($channelPricing);

            $product->addVariant($variant);

            $manager->persist($product);
            $manager->persist($variant);
        }

        $manager->flush();

        return $channel;
    }

    /**
     * A `mixed` parameter is a hard type boundary: league/csv types getRecords() records loosely on
     * lower versions and precisely on newer ones, so the guard is neither too narrow nor redundant.
     */
    private static function rowId(mixed $row): mixed
    {
        return is_array($row) ? ($row['id'] ?? null) : null;
    }
}
