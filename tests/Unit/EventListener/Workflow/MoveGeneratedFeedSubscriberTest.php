<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\EventListener\Workflow;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\EventListener\Workflow\MoveGeneratedFeedSubscriber;
use Setono\SyliusFeedPlugin\Model\Feed;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @covers \Setono\SyliusFeedPlugin\EventListener\Workflow\MoveGeneratedFeedSubscriber
 */
final class MoveGeneratedFeedSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_subscribes_to_the_complete_transition(): void
    {
        self::assertArrayHasKey(
            'workflow.setono_sylius_feed_feed.completed.complete',
            MoveGeneratedFeedSubscriber::getSubscribedEvents(),
        );
    }

    /**
     * @test
     */
    public function it_swaps_temporary_files_to_canonical_storage_and_stamps_the_feed(): void
    {
        $stream = fopen('php://temp', 'r');
        self::assertIsResource($stream);

        $listing = (static function (): \Generator {
            yield new DirectoryAttributes('google/nested'); // not a file -> skipped
            yield new FileAttributes('google/web_en_us_usd.xml');
        })();

        $temporary = $this->prophesize(FilesystemOperator::class);
        $temporary->listContents('google', true)->willReturn(new DirectoryListing($listing));
        $temporary->readStream('google/web_en_us_usd.xml')->willReturn($stream);
        $temporary->deleteDirectory('google')->shouldBeCalled();

        $canonical = $this->prophesize(FilesystemOperator::class);
        $canonical->deleteDirectory('google')->shouldBeCalled();
        $canonical->writeStream('google/web_en_us_usd.xml', $stream)->shouldBeCalled();

        $feed = new Feed();
        $feed->setCode('google');

        (new MoveGeneratedFeedSubscriber($temporary->reveal(), $canonical->reveal()))->onCompleted($this->event($feed));

        self::assertNotNull($feed->getLastGeneratedAt());

        fclose($stream);
    }

    /**
     * @test
     */
    public function it_ignores_a_non_feed_subject(): void
    {
        $temporary = $this->prophesize(FilesystemOperator::class);
        $temporary->deleteDirectory(Argument::any())->shouldNotBeCalled();

        (new MoveGeneratedFeedSubscriber($temporary->reveal(), $this->prophesize(FilesystemOperator::class)->reveal()))
            ->onCompleted($this->event(new \stdClass()));

        self::addToAssertionCount(1);
    }

    private function event(object $subject): CompletedEvent
    {
        return new CompletedEvent(
            $subject,
            new Marking(['completed' => 1]),
            new Transition('complete', 'processing', 'completed'),
            $this->prophesize(WorkflowInterface::class)->reveal(),
        );
    }
}
