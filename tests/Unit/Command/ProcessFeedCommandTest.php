<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Audit\AuditReport;
use Setono\SyliusFeedPlugin\Audit\FeedAuditServiceInterface;
use Setono\SyliusFeedPlugin\Command\ProcessFeedCommand;
use Setono\SyliusFeedPlugin\Context\ContextFactoryInterface;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Preview\PreviewFunnel;
use Setono\SyliusFeedPlugin\Preview\PreviewResult;
use Setono\SyliusFeedPlugin\Preview\PreviewServiceInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

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

        $tester = new CommandTester($this->command($repository->reveal(), $commandBus->reveal()));
        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('google', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_dispatches_a_process_message_for_each_enabled_feed_with_the_all_option(): void
    {
        $feed = $this->feed();

        $repository = $this->prophesize(RepositoryInterface::class);
        $repository->findBy(['enabled' => true])->willReturn([$feed]);

        $commandBus = $this->prophesize(MessageBusInterface::class);
        $commandBus->dispatch(Argument::type(ProcessFeed::class))->willReturn(new Envelope(new \stdClass()))->shouldBeCalledOnce();

        $tester = new CommandTester($this->command($repository->reveal(), $commandBus->reveal()));

        self::assertSame(Command::SUCCESS, $tester->execute(['--all' => true]));
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

        $tester = new CommandTester($this->command($repository->reveal(), $commandBus->reveal()));

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

        $tester = new CommandTester($this->command($repository->reveal(), $commandBus->reveal()));
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

        $tester = new CommandTester($this->command($repository->reveal(), $commandBus->reveal()));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
    }

    /**
     * @test
     */
    public function it_previews_the_funnel_instead_of_generating_when_preview_is_passed(): void
    {
        $feed = $this->feed();
        $context = new FeedContext(null, 'en_US', 'USD');

        $repository = $this->prophesize(RepositoryInterface::class);
        $repository->findBy(['enabled' => true])->willReturn([$feed]);

        $commandBus = $this->prophesize(MessageBusInterface::class);
        $commandBus->dispatch(Argument::cetera())->shouldNotBeCalled();

        $contextFactory = $this->prophesize(ContextFactoryInterface::class);
        $contextFactory->create($feed)->willReturn([$context]);

        $preview = new PreviewResult(
            new PreviewFunnel(3, 2, 1, 1, 1),
            [['id' => 'SKU-1']],
            [['item' => 'SKU-2', 'reason' => 'filter:pre:availability']],
        );
        $previewService = $this->prophesize(PreviewServiceInterface::class);
        $previewService->preview($feed, $context, 3)->willReturn($preview)->shouldBeCalledOnce();

        $auditService = $this->prophesize(FeedAuditServiceInterface::class);
        $auditService->audit(Argument::cetera())->shouldNotBeCalled();

        $tester = new CommandTester($this->command(
            $repository->reveal(),
            $commandBus->reveal(),
            $contextFactory->reveal(),
            $previewService->reveal(),
            $auditService->reveal(),
        ));

        self::assertSame(Command::SUCCESS, $tester->execute(['--preview' => '3']));

        $display = $tester->getDisplay();
        self::assertStringContainsString('SKU-1', $display);
        self::assertStringContainsString('filter:pre:availability', $display);
    }

    /**
     * @test
     */
    public function it_prints_the_audit_when_audit_is_passed(): void
    {
        $feed = $this->feed();
        $context = new FeedContext(null, 'en_US', 'USD');

        $repository = $this->prophesize(RepositoryInterface::class);
        $repository->findBy(['enabled' => true])->willReturn([$feed]);

        $commandBus = $this->prophesize(MessageBusInterface::class);
        $commandBus->dispatch(Argument::cetera())->shouldNotBeCalled();

        $contextFactory = $this->prophesize(ContextFactoryInterface::class);
        $contextFactory->create($feed)->willReturn([$context]);

        $preview = new PreviewResult(new PreviewFunnel(1, 1, 1, 1, 1), [['g:title' => 'x']], []);
        $previewService = $this->prophesize(PreviewServiceInterface::class);
        $previewService->preview($feed, $context, 50)->willReturn($preview);

        $audit = new AuditReport(['g:title' => 1.0], [['type' => 'title_too_long', 'field' => 'g:title', 'count' => 1]], []);
        $auditService = $this->prophesize(FeedAuditServiceInterface::class);
        $auditService->audit($preview)->willReturn($audit)->shouldBeCalledOnce();

        $tester = new CommandTester($this->command(
            $repository->reveal(),
            $commandBus->reveal(),
            $contextFactory->reveal(),
            $previewService->reveal(),
            $auditService->reveal(),
        ));

        self::assertSame(Command::SUCCESS, $tester->execute(['--audit' => true]));

        $display = $tester->getDisplay();
        self::assertStringContainsString('title_too_long', $display);
        self::assertStringContainsString('100.0%', $display);
    }

    private function command(
        RepositoryInterface $repository,
        MessageBusInterface $commandBus,
        ?ContextFactoryInterface $contextFactory = null,
        ?PreviewServiceInterface $previewService = null,
        ?FeedAuditServiceInterface $auditService = null,
    ): ProcessFeedCommand {
        return new ProcessFeedCommand(
            $repository,
            $commandBus,
            $contextFactory ?? $this->prophesize(ContextFactoryInterface::class)->reveal(),
            $previewService ?? $this->prophesize(PreviewServiceInterface::class)->reveal(),
            $auditService ?? $this->prophesize(FeedAuditServiceInterface::class)->reveal(),
        );
    }
}
