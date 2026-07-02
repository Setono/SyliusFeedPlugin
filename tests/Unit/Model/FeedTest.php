<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedSource;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\ChannelInterface;

final class FeedTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_starts_ready_enabled_and_empty(): void
    {
        $feed = new Feed();

        self::assertNull($feed->getId());
        self::assertTrue($feed->isEnabled());
        self::assertSame(FeedGraph::STATE_READY, $feed->getState());
        self::assertSame([], $feed->getFormatConfig());
        self::assertSame([], $feed->getPublishConfig());
        self::assertNull($feed->getLastGeneratedAt());
        self::assertCount(0, $feed->getChannels());
        self::assertCount(0, $feed->getSources());
    }

    /**
     * @test
     */
    public function it_holds_its_scalar_configuration(): void
    {
        $generatedAt = new \DateTimeImmutable('2026-01-01 12:00:00');

        $feed = new Feed();
        $feed->setCode('google');
        $feed->setEnabled(false);
        $feed->setFormat('google_rss');
        $feed->setState(FeedGraph::STATE_PROCESSING);
        $feed->setFormatConfig(['gzip' => true]);
        $feed->setPublishConfig(['guardrails' => [['type' => 'non_empty', 'severity' => 'block']]]);
        $feed->setLastGeneratedAt($generatedAt);

        self::assertSame('google', $feed->getCode());
        self::assertFalse($feed->isEnabled());
        self::assertSame('google_rss', $feed->getFormat());
        self::assertSame(FeedGraph::STATE_PROCESSING, $feed->getState());
        self::assertSame(['gzip' => true], $feed->getFormatConfig());
        self::assertSame(['guardrails' => [['type' => 'non_empty', 'severity' => 'block']]], $feed->getPublishConfig());
        self::assertSame($generatedAt, $feed->getLastGeneratedAt());
    }

    /**
     * @test
     */
    public function it_manages_channels(): void
    {
        $channel = $this->prophesize(ChannelInterface::class)->reveal();

        $feed = new Feed();
        $feed->addChannel($channel);

        self::assertTrue($feed->hasChannel($channel));
        self::assertCount(1, $feed->getChannels());

        $feed->addChannel($channel);
        self::assertCount(1, $feed->getChannels());

        $feed->removeChannel($channel);
        self::assertFalse($feed->hasChannel($channel));
    }

    /**
     * @test
     */
    public function it_manages_sources_and_sets_the_back_reference(): void
    {
        $source = new FeedSource();

        $feed = new Feed();
        $feed->addSource($source);

        self::assertTrue($feed->hasSource($source));
        self::assertSame($feed, $source->getFeed());

        $feed->removeSource($source);

        self::assertFalse($feed->hasSource($source));
        self::assertNull($source->getFeed());
    }

    /**
     * @test
     */
    public function it_proxies_name_and_slug_to_the_current_translation(): void
    {
        $feed = new Feed();
        $feed->setCurrentLocale('en_US');
        $feed->setName('Google Shopping');
        $feed->setSlug('google-shopping');

        self::assertSame('Google Shopping', $feed->getName());
        self::assertSame('google-shopping', $feed->getSlug());
    }
}
