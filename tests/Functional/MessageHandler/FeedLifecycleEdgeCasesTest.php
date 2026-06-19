<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Functional\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusFeedPlugin\Message\Command\GenerateFeedContext;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Model\FeedSource;
use Setono\SyliusFeedPlugin\Tests\Functional\FunctionalTestCase;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Edge cases of the async lifecycle handlers: feeds with no contexts, missing/non-processable feeds,
 * and generation failures transitioning the feed to `failed`.
 */
final class FeedLifecycleEdgeCasesTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function it_completes_a_feed_that_has_no_contexts(): void
    {
        // a product_variant source declares the CHANNEL scope, but the feed has no channels
        $feedId = (int) $this->persistFeed('google_rss', FeedGraph::STATE_READY)->getId();

        $this->dispatch(new ProcessFeed($feedId));

        $feed = $this->reload($feedId);
        self::assertSame(FeedGraph::STATE_COMPLETED, $feed->getState());
        self::assertSame(0, $feed->getContextCount());
    }

    /**
     * @test
     */
    public function it_ignores_processing_a_missing_feed(): void
    {
        $this->dispatch(new ProcessFeed(999999));

        self::addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_does_not_process_a_feed_that_is_not_ready(): void
    {
        $feedId = (int) $this->persistFeed('google_rss', FeedGraph::STATE_COMPLETED)->getId();

        $this->dispatch(new ProcessFeed($feedId));

        self::assertSame(FeedGraph::STATE_COMPLETED, $this->reload($feedId)->getState());
    }

    /**
     * @test
     */
    public function it_ignores_generating_a_context_for_a_missing_feed(): void
    {
        $this->dispatch(new GenerateFeedContext(999999, 'web', 'en_US', 'USD'));

        self::addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_fails_the_feed_when_generation_throws(): void
    {
        // an unknown format makes the generator throw; the feed must be processing to fail
        $feedId = (int) $this->persistFeed('this_format_does_not_exist', FeedGraph::STATE_PROCESSING)->getId();

        try {
            $this->dispatch(new GenerateFeedContext($feedId, 'ghost-channel', 'en_US', 'USD'));
            self::fail('Expected the generation to throw');
        } catch (\Throwable) {
            // the handler rethrows after transitioning to failed
        }

        self::assertSame(FeedGraph::STATE_FAILED, $this->reload($feedId)->getState());
    }

    private function dispatch(object $message): void
    {
        $bus = self::getContainer()->get('setono_sylius_feed.command_bus');
        self::assertInstanceOf(MessageBusInterface::class, $bus);
        $bus->dispatch($message);
    }

    private function reload(int $feedId): FeedInterface
    {
        $this->entityManager()->clear();
        $repository = self::getContainer()->get('setono_sylius_feed.repository.feed');
        self::assertInstanceOf(RepositoryInterface::class, $repository);
        $feed = $repository->find($feedId);
        self::assertInstanceOf(FeedInterface::class, $feed);

        return $feed;
    }

    private function entityManager(): EntityManagerInterface
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
    }

    private function persistFeed(string $format, string $state): FeedInterface
    {
        $feed = new Feed();
        $feed->setCode('edge-feed');
        $feed->setFormat($format);
        $feed->setEnabled(true);
        $feed->setState($state);
        $feed->setCurrentLocale('en_US');
        $feed->setName('Edge feed');
        $feed->setSlug('edge-feed');

        $source = new FeedSource();
        $source->setFeedType('product_variant');
        $source->setPosition(0);
        $feed->addSource($source);

        $manager = $this->entityManager();
        $manager->persist($feed);
        $manager->flush();

        return $feed;
    }
}
