<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\EventListener\Workflow;

use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\EventListener\Workflow\DeleteGeneratedFilesSubscriber;
use Setono\SyliusFeedPlugin\Model\Feed;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @covers \Setono\SyliusFeedPlugin\EventListener\Workflow\DeleteGeneratedFilesSubscriber
 */
final class DeleteGeneratedFilesSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function it_subscribes_to_the_fail_and_reset_transitions(): void
    {
        $events = DeleteGeneratedFilesSubscriber::getSubscribedEvents();

        self::assertArrayHasKey('workflow.setono_sylius_feed_feed.completed.fail', $events);
        self::assertArrayHasKey('workflow.setono_sylius_feed_feed.completed.reset', $events);
    }

    /**
     * @test
     */
    public function it_deletes_the_temporary_files_of_the_feed(): void
    {
        $temporary = $this->prophesize(FilesystemOperator::class);
        $temporary->deleteDirectory('google')->shouldBeCalled();

        $feed = new Feed();
        $feed->setCode('google');

        (new DeleteGeneratedFilesSubscriber($temporary->reveal()))->onCompleted($this->event($feed));

        self::addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function it_ignores_a_non_feed_subject(): void
    {
        $temporary = $this->prophesize(FilesystemOperator::class);
        $temporary->deleteDirectory(Argument::any())->shouldNotBeCalled();

        (new DeleteGeneratedFilesSubscriber($temporary->reveal()))->onCompleted($this->event(new \stdClass()));

        self::addToAssertionCount(1);
    }

    private function event(object $subject): CompletedEvent
    {
        return new CompletedEvent(
            $subject,
            new Marking(['completed' => 1]),
            new Transition('fail', 'processing', 'failed'),
            $this->prophesize(WorkflowInterface::class)->reveal(),
        );
    }
}
