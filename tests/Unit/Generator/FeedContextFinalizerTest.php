<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Generator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Generator\FeedContextFinalizer;
use Setono\SyliusFeedPlugin\Generator\FeedContextResultRecorderInterface;
use Setono\SyliusFeedPlugin\Generator\GenerationResult;
use Setono\SyliusFeedPlugin\Model\Feed;
use Setono\SyliusFeedPlugin\Model\FeedContextResult;
use Setono\SyliusFeedPlugin\Publish\PublishDecision;
use Setono\SyliusFeedPlugin\Publish\PublishGateInterface;
use Setono\SyliusFeedPlugin\Repository\FeedContextResultRepositoryInterface;
use Setono\SyliusFeedPlugin\Repository\FeedRepositoryInterface;
use Setono\SyliusFeedPlugin\Workflow\FeedGraph;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Unit tests for the shared finalize collaborator {@see FeedContextFinalizer}: it records the
 * candidate, stamps the context's dimension codes, runs the publish gate, and completes the feed
 * only once the last context of the run has finished.
 */
final class FeedContextFinalizerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_records_stamps_and_completes_the_feed_on_the_last_context(): void
    {
        $feed = new Feed();
        $feed->setContextCount(2);

        $candidate = new FeedContextResult();
        $result = new GenerationResult('catalog/web_en_us_usd.csv', 5, 0);

        $recorder = $this->prophesize(FeedContextResultRecorderInterface::class);
        $recorder->record($feed, 'web_en_us_usd', $result)->willReturn($candidate);

        $resultRepository = $this->prophesize(FeedContextResultRepositoryInterface::class);
        $resultRepository->findLatestPublished($feed, 'web_en_us_usd')->willReturn(null);

        $publishGate = $this->prophesize(PublishGateInterface::class);
        $publishGate->evaluate($candidate, null, [])->willReturn(new PublishDecision(false));

        $feedRepository = $this->prophesize(FeedRepositoryInterface::class);
        // second (last) context reaches the total
        $feedRepository->incrementCompletedContexts($feed)->willReturn(2);

        $workflow = $this->prophesize(WorkflowInterface::class);
        $workflow->can($feed, FeedGraph::TRANSITION_COMPLETE)->willReturn(true);
        $workflow->apply($feed, FeedGraph::TRANSITION_COMPLETE)->shouldBeCalled()->willReturn(new Marking());

        $workflowRegistry = $this->prophesize(Registry::class);
        $workflowRegistry->get($feed, FeedGraph::GRAPH)->willReturn($workflow->reveal());

        $this->finalizer($feedRepository, $recorder, $resultRepository, $publishGate, $workflowRegistry)
            ->finalize($feed, new FeedContext($this->channel(), 'en_US', 'USD'), $result);

        self::assertSame('web', $candidate->getChannelCode());
        self::assertSame('en_US', $candidate->getLocaleCode());
        self::assertSame('USD', $candidate->getCurrencyCode());
        self::assertSame(FeedContextResult::PUBLISH_STATE_PUBLISHED, $candidate->getPublishState());
    }

    /**
     * @test
     */
    public function it_does_not_complete_the_feed_before_the_last_context(): void
    {
        $feed = new Feed();
        $feed->setContextCount(3);

        $candidate = new FeedContextResult();
        $result = new GenerationResult('catalog/web_en_us_usd.csv', 5, 0);

        $recorder = $this->prophesize(FeedContextResultRecorderInterface::class);
        $recorder->record($feed, 'web_en_us_usd', $result)->willReturn($candidate);

        $resultRepository = $this->prophesize(FeedContextResultRepositoryInterface::class);
        $resultRepository->findLatestPublished($feed, 'web_en_us_usd')->willReturn(null);

        $publishGate = $this->prophesize(PublishGateInterface::class);
        $publishGate->evaluate($candidate, null, [])->willReturn(new PublishDecision(false));

        $feedRepository = $this->prophesize(FeedRepositoryInterface::class);
        $feedRepository->incrementCompletedContexts($feed)->willReturn(1);

        $workflowRegistry = $this->prophesize(Registry::class);
        // the feed is not completed yet, so the workflow is never touched
        $workflowRegistry->get(Argument::cetera())->shouldNotBeCalled();

        $this->finalizer($feedRepository, $recorder, $resultRepository, $publishGate, $workflowRegistry)
            ->finalize($feed, new FeedContext($this->channel(), 'en_US', 'USD'), $result);
    }

    /**
     * @param \Prophecy\Prophecy\ObjectProphecy<FeedRepositoryInterface>                $feedRepository
     * @param \Prophecy\Prophecy\ObjectProphecy<FeedContextResultRecorderInterface>     $recorder
     * @param \Prophecy\Prophecy\ObjectProphecy<FeedContextResultRepositoryInterface>   $resultRepository
     * @param \Prophecy\Prophecy\ObjectProphecy<PublishGateInterface>                   $publishGate
     * @param \Prophecy\Prophecy\ObjectProphecy<Registry>                               $workflowRegistry
     */
    private function finalizer(
        object $feedRepository,
        object $recorder,
        object $resultRepository,
        object $publishGate,
        object $workflowRegistry,
    ): FeedContextFinalizer {
        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->flush()->willReturn(null);

        $managerRegistry = $this->prophesize(ManagerRegistry::class);
        $managerRegistry->getManagerForClass(Argument::any())->willReturn($manager->reveal());

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        return new FeedContextFinalizer(
            $managerRegistry->reveal(),
            $feedRepository->reveal(),
            $recorder->reveal(),
            $resultRepository->reveal(),
            $publishGate->reveal(),
            $eventDispatcher->reveal(),
            $workflowRegistry->reveal(),
        );
    }

    private function channel(): ChannelInterface
    {
        $channel = $this->prophesize(ChannelInterface::class);
        $channel->getCode()->willReturn('web');

        return $channel->reveal();
    }
}
