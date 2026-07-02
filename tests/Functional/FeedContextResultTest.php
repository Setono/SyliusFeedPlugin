<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusFeedPlugin\Generator\FeedContextResultRecorderInterface;
use Setono\SyliusFeedPlugin\Generator\GenerationResult;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Repository\FeedContextResultRepositoryInterface;

/**
 * Proves the FeedContextResult resource is mapped and persistable end to end (§11): the recorder
 * writes a result against a real feed and the repository reads back the most recent one per
 * (feed, contextKey) via findLatestPublished. Uses the database (rolled back by dama).
 */
final class FeedContextResultTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_records_a_result_and_reads_back_the_latest_per_context(): void
    {
        $feed = $this->persistFeed();
        $recorder = $this->recorder();

        $recorder->record($feed, 'web_en_US_USD', new GenerationResult(
            'google/web_en_US_USD.xml',
            5,
            2,
            512,
            [['item' => 'SKU-9', 'reason' => 'validation:setono_sylius_feed.google_shopping_item.title.not_blank']],
        ));

        // a later run for the same context supersedes the first
        $recorder->record($feed, 'web_en_US_USD', new GenerationResult('google/web_en_US_USD.xml', 7, 0, 700, []));

        // a different context is stored independently
        $recorder->record($feed, 'web_da_DK_DKK', new GenerationResult('google/web_da_DK_DKK.xml', 1, 0, 64, []));

        $repository = $this->repository();

        $latest = $repository->findLatestPublished($feed, 'web_en_US_USD');
        self::assertInstanceOf(FeedContextResultInterface::class, $latest);
        self::assertSame(7, $latest->getItemCount());
        self::assertSame(0, $latest->getExcludedCount());
        self::assertSame(700, $latest->getBytes());
        self::assertSame([], $latest->getErrors());
        self::assertSame($feed->getId(), $latest->getFeed()?->getId());

        $otherContext = $repository->findLatestPublished($feed, 'web_da_DK_DKK');
        self::assertInstanceOf(FeedContextResultInterface::class, $otherContext);
        self::assertSame(1, $otherContext->getItemCount());

        self::assertNull($repository->findLatestPublished($feed, 'does_not_exist'));
    }

    private function persistFeed(): FeedInterface
    {
        $feed = new Feed();
        $feed->setCode('feed-context-result-' . bin2hex(random_bytes(4)));

        $manager = $this->entityManager();
        $manager->persist($feed);
        $manager->flush();

        return $feed;
    }

    private function recorder(): FeedContextResultRecorderInterface
    {
        $recorder = self::getContainer()->get(FeedContextResultRecorderInterface::class);
        self::assertInstanceOf(FeedContextResultRecorderInterface::class, $recorder);

        return $recorder;
    }

    private function repository(): FeedContextResultRepositoryInterface
    {
        $repository = self::getContainer()->get('setono_sylius_feed.repository.feed_context_result');
        self::assertInstanceOf(FeedContextResultRepositoryInterface::class, $repository);

        return $repository;
    }

    private function entityManager(): EntityManagerInterface
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
    }
}
