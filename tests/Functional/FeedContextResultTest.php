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
 * Proves the FeedContextResult resource is mapped and persistable end to end (§11) and that the
 * publish-gate lookups behave (§6.6): findLatestPublished only sees `published` rows (so a pending
 * candidate is never its own baseline), while findLatestForContext returns the most recent row
 * regardless of state. Uses the database (rolled back by dama).
 */
final class FeedContextResultTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_does_not_treat_a_pending_candidate_as_its_own_baseline(): void
    {
        $feed = $this->persistFeed();
        $repository = $this->repository();

        // A freshly recorded candidate is `pending`...
        $candidate = $this->recorder()->record($feed, 'web_en_US_USD', new GenerationResult('google/web_en_US_USD.xml', 5, 2, 512, []));

        // ...so it is not yet a published baseline, but it is the latest result for the context.
        self::assertNull($repository->findLatestPublished($feed, 'web_en_US_USD'));
        self::assertSame($candidate->getId(), $repository->findLatestForContext($feed, 'web_en_US_USD')?->getId());
        self::assertNull($repository->findLatestForContext($feed, 'does_not_exist'));
    }

    /**
     * @test
     */
    public function it_returns_the_latest_published_row_as_the_baseline(): void
    {
        $feed = $this->persistFeed();
        $recorder = $this->recorder();
        $repository = $this->repository();
        $manager = $this->entityManager();

        // Publish a first run for the context.
        $first = $recorder->record($feed, 'web_en_US_USD', new GenerationResult('google/web_en_US_USD.xml', 5, 2, 512, []));
        $first->setPublishState(FeedContextResultInterface::PUBLISH_STATE_PUBLISHED);
        $manager->flush();

        $baseline = $repository->findLatestPublished($feed, 'web_en_US_USD');
        self::assertInstanceOf(FeedContextResultInterface::class, $baseline);
        self::assertSame($first->getId(), $baseline->getId());

        // A later run supersedes the first as the latest candidate, but while it is still pending the
        // baseline remains the previously published row.
        $second = $recorder->record($feed, 'web_en_US_USD', new GenerationResult('google/web_en_US_USD.xml', 7, 0, 700, []));
        self::assertSame($second->getId(), $repository->findLatestForContext($feed, 'web_en_US_USD')?->getId());
        self::assertSame($first->getId(), $repository->findLatestPublished($feed, 'web_en_US_USD')?->getId());

        // Publishing the second run makes it the new baseline.
        $second->setPublishState(FeedContextResultInterface::PUBLISH_STATE_PUBLISHED);
        $manager->flush();
        $newBaseline = $repository->findLatestPublished($feed, 'web_en_US_USD');
        self::assertInstanceOf(FeedContextResultInterface::class, $newBaseline);
        self::assertSame(7, $newBaseline->getItemCount());
        self::assertSame(700, $newBaseline->getBytes());
        self::assertSame($feed->getId(), $newBaseline->getFeed()?->getId());

        // A different context is stored independently.
        $recorder->record($feed, 'web_da_DK_DKK', new GenerationResult('google/web_da_DK_DKK.xml', 1, 0, 64, []));
        self::assertSame(1, $repository->findLatestForContext($feed, 'web_da_DK_DKK')?->getItemCount());
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
