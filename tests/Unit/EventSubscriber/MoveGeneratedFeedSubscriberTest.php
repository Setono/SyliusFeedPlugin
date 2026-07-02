<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\EventSubscriber;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Delivery\DeliveryServiceInterface;
use Setono\SyliusFeedPlugin\EventSubscriber\MoveGeneratedFeedSubscriber;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedContextResult;
use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;
use Setono\SyliusFeedPlugin\Repository\FeedContextResultRepositoryInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

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
    public function it_promotes_published_contexts_and_retains_blocked_ones(): void
    {
        $publishedStream = fopen('php://temp', 'r');
        self::assertIsResource($publishedStream);
        $ungatedStream = fopen('php://temp', 'r');
        self::assertIsResource($ungatedStream);

        $listing = (static function (): \Generator {
            yield new DirectoryAttributes('google/nested'); // not a file -> skipped
            yield new FileAttributes('google/web_en_us_usd.xml'); // published -> promoted + delivered
            yield new FileAttributes('google/web_da_dk_dkk.xml'); // blocked -> retained + not delivered
            yield new FileAttributes('google/web_sv_se_sek.xml'); // no result -> promoted (no gate), not delivered
        })();

        $temporary = $this->prophesize(FilesystemOperator::class);
        $temporary->listContents('google', true)->willReturn(new DirectoryListing($listing));
        $temporary->readStream('google/web_en_us_usd.xml')->willReturn($publishedStream);
        $temporary->readStream('google/web_sv_se_sek.xml')->willReturn($ungatedStream);
        $temporary->readStream('google/web_da_dk_dkk.xml')->shouldNotBeCalled();
        $temporary->delete('google/web_en_us_usd.xml')->shouldBeCalledOnce();
        $temporary->delete('google/web_sv_se_sek.xml')->shouldBeCalledOnce();
        $temporary->delete('google/web_da_dk_dkk.xml')->shouldNotBeCalled();

        $canonical = $this->prophesize(FilesystemOperator::class);
        $canonical->writeStream('google/web_en_us_usd.xml', $publishedStream)->shouldBeCalledOnce();
        $canonical->writeStream('google/web_sv_se_sek.xml', $ungatedStream)->shouldBeCalledOnce();
        $canonical->writeStream('google/web_da_dk_dkk.xml', Argument::any())->shouldNotBeCalled();
        $canonical->deleteDirectory(Argument::any())->shouldNotBeCalled();

        $feed = new Feed();
        $feed->setCode('google');

        $published = $this->result(FeedContextResultInterface::PUBLISH_STATE_PUBLISHED);

        $repository = $this->prophesize(FeedContextResultRepositoryInterface::class);
        $repository->findLatestForContext($feed, 'web_en_us_usd')->willReturn($published);
        $repository->findLatestForContext($feed, 'web_da_dk_dkk')->willReturn($this->result(FeedContextResultInterface::PUBLISH_STATE_BLOCKED));
        $repository->findLatestForContext($feed, 'web_sv_se_sek')->willReturn(null);

        $deliveryService = $this->prophesize(DeliveryServiceInterface::class);
        // Only the published context is delivered; the blocked and the ungated ones are not.
        $deliveryService->deliver($feed, $published, $published->getPaths())->shouldBeCalledOnce();

        (new MoveGeneratedFeedSubscriber($temporary->reveal(), $canonical->reveal(), $repository->reveal(), $deliveryService->reveal()))
            ->onCompleted($this->event($feed));

        self::assertNotNull($feed->getLastGeneratedAt());

        fclose($publishedStream);
        fclose($ungatedStream);
    }

    /**
     * @test
     */
    public function it_does_not_deliver_a_blocked_context(): void
    {
        $listing = (static function (): \Generator {
            yield new FileAttributes('google/web_da_dk_dkk.xml'); // blocked
        })();

        $temporary = $this->prophesize(FilesystemOperator::class);
        $temporary->listContents('google', true)->willReturn(new DirectoryListing($listing));
        $temporary->readStream(Argument::any())->shouldNotBeCalled();
        $temporary->delete(Argument::any())->shouldNotBeCalled();

        $canonical = $this->prophesize(FilesystemOperator::class);
        $canonical->writeStream(Argument::cetera())->shouldNotBeCalled();

        $feed = new Feed();
        $feed->setCode('google');

        $repository = $this->prophesize(FeedContextResultRepositoryInterface::class);
        $repository->findLatestForContext($feed, 'web_da_dk_dkk')->willReturn($this->result(FeedContextResultInterface::PUBLISH_STATE_BLOCKED));

        $deliveryService = $this->prophesize(DeliveryServiceInterface::class);
        $deliveryService->deliver(Argument::cetera())->shouldNotBeCalled();

        (new MoveGeneratedFeedSubscriber($temporary->reveal(), $canonical->reveal(), $repository->reveal(), $deliveryService->reveal()))
            ->onCompleted($this->event($feed));

        self::addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_ignores_a_non_feed_subject(): void
    {
        $temporary = $this->prophesize(FilesystemOperator::class);
        $temporary->listContents(Argument::cetera())->shouldNotBeCalled();

        (new MoveGeneratedFeedSubscriber(
            $temporary->reveal(),
            $this->prophesize(FilesystemOperator::class)->reveal(),
            $this->prophesize(FeedContextResultRepositoryInterface::class)->reveal(),
            $this->prophesize(DeliveryServiceInterface::class)->reveal(),
        ))->onCompleted($this->event(new \stdClass()));

        self::addToAssertionCount(1);
    }

    private function result(string $publishState): FeedContextResult
    {
        $result = new FeedContextResult();
        $result->setPublishState($publishState);

        return $result;
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
