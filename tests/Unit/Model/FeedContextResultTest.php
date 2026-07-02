<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Model\FeedContextResult;
use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;

final class FeedContextResultTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_exposes_its_accessors(): void
    {
        $feed = $this->prophesize(FeedInterface::class)->reveal();
        $createdAt = new \DateTimeImmutable('2026-07-02 10:00:00');

        $result = new FeedContextResult();
        $result->setFeed($feed);
        $result->setContextKey('web_en_US_USD');
        $result->setItemCount(42);
        $result->setExcludedCount(3);
        $result->setBytes(1024);
        $result->setErrors([['item' => 'SKU-1', 'reason' => 'validation:missing title']]);
        $result->setPublishState(FeedContextResultInterface::PUBLISH_STATE_BLOCKED);
        $result->setPublishCheck(['max_drop_pct: guardrail tripped (severity: block)']);
        $result->setCreatedAt($createdAt);

        self::assertNull($result->getId());
        self::assertSame($feed, $result->getFeed());
        self::assertSame('web_en_US_USD', $result->getContextKey());
        self::assertSame(42, $result->getItemCount());
        self::assertSame(3, $result->getExcludedCount());
        self::assertSame(1024, $result->getBytes());
        self::assertSame([['item' => 'SKU-1', 'reason' => 'validation:missing title']], $result->getErrors());
        self::assertSame(FeedContextResultInterface::PUBLISH_STATE_BLOCKED, $result->getPublishState());
        self::assertSame(['max_drop_pct: guardrail tripped (severity: block)'], $result->getPublishCheck());
        self::assertSame($createdAt, $result->getCreatedAt());
    }

    /**
     * @test
     */
    public function it_starts_pending_with_no_publish_check(): void
    {
        $result = new FeedContextResult();

        self::assertSame(FeedContextResultInterface::PUBLISH_STATE_PENDING, $result->getPublishState());
        self::assertNull($result->getPublishCheck());
        self::assertFalse($result->isPublished());
        self::assertFalse($result->isBlocked());
    }

    /**
     * @test
     */
    public function it_reflects_its_publish_state_through_predicates(): void
    {
        $result = new FeedContextResult();

        $result->setPublishState(FeedContextResultInterface::PUBLISH_STATE_PUBLISHED);
        self::assertTrue($result->isPublished());
        self::assertFalse($result->isBlocked());

        $result->setPublishState(FeedContextResultInterface::PUBLISH_STATE_BLOCKED);
        self::assertFalse($result->isPublished());
        self::assertTrue($result->isBlocked());
    }

    /**
     * @test
     */
    public function it_defaults_created_at_to_now(): void
    {
        self::assertEqualsWithDelta(time(), (new FeedContextResult())->getCreatedAt()->getTimestamp(), 5);
    }

    /**
     * @test
     */
    public function it_appends_errors(): void
    {
        $result = new FeedContextResult();
        $result->addError('SKU-1', 'filter:pre:availability');
        $result->addError(null, 'skipped');

        self::assertSame([
            ['item' => 'SKU-1', 'reason' => 'filter:pre:availability'],
            ['item' => null, 'reason' => 'skipped'],
        ], $result->getErrors());
    }
}
