<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Command\ProcessFeedCommand;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @covers \Setono\SyliusFeedPlugin\Command\ProcessFeedCommand
 */
final class ProcessFeedCommandTest extends TestCase
{
    use ProphecyTrait;

    private function feed(): FeedInterface
    {
        $feed = $this->prophesize(FeedInterface::class);
        $feed->getId()->willReturn(1);
        $feed->getCode()->willReturn('google');

        return $feed->reveal();
    }

    /**
     * @test
     */
    public function it_dispatches_a_process_message_for_each_enabled_feed(): void
    {
        $feed = $this->feed();

        $repository = $this->prophesize(RepositoryInterface::class);
        $repository->findBy(['enabled' => true])->willReturn([$feed]);

        $commandBus = $this->prophesize(MessageBusInterface::class);
        $commandBus->dispatch(Argument::type(ProcessFeed::class))->willReturn(new Envelope(new \stdClass()))->shouldBeCalledOnce();

        $tester = new CommandTester(new ProcessFeedCommand($repository->reveal(), $commandBus->reveal()));
        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('google', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_dispatches_only_the_requested_feed(): void
    {
        $feed = $this->feed();

        $repository = $this->prophesize(RepositoryInterface::class);
        $repository->findOneBy(['code' => 'google'])->willReturn($feed);

        $commandBus = $this->prophesize(MessageBusInterface::class);
        $commandBus->dispatch(Argument::type(ProcessFeed::class))->willReturn(new Envelope(new \stdClass()))->shouldBeCalledOnce();

        $tester = new CommandTester(new ProcessFeedCommand($repository->reveal(), $commandBus->reveal()));

        self::assertSame(Command::SUCCESS, $tester->execute(['--feed' => 'google']));
    }

    /**
     * @test
     */
    public function it_warns_when_there_are_no_feeds_to_process(): void
    {
        $repository = $this->prophesize(RepositoryInterface::class);
        $repository->findBy(['enabled' => true])->willReturn([]);

        $commandBus = $this->prophesize(MessageBusInterface::class);
        $commandBus->dispatch(Argument::cetera())->shouldNotBeCalled();

        $tester = new CommandTester(new ProcessFeedCommand($repository->reveal(), $commandBus->reveal()));
        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('No feeds', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_skips_repository_results_that_are_not_feeds(): void
    {
        $repository = $this->prophesize(RepositoryInterface::class);
        $repository->findBy(['enabled' => true])->willReturn([new \stdClass()]);

        $commandBus = $this->prophesize(MessageBusInterface::class);
        $commandBus->dispatch(Argument::cetera())->shouldNotBeCalled();

        $tester = new CommandTester(new ProcessFeedCommand($repository->reveal(), $commandBus->reveal()));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }
}
