<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusFeedPlugin\Command\ProcessFeedCommand;
use Setono\SyliusFeedPlugin\Context\ContextFactoryInterface;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Generator\FeedGeneratorInterface;
use Setono\SyliusFeedPlugin\Generator\GenerationResult;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Setono\SyliusFeedPlugin\Command\ProcessFeedCommand
 */
final class ProcessFeedCommandTest extends TestCase
{
    use ProphecyTrait;

    private function feed(): FeedInterface
    {
        $feed = $this->prophesize(FeedInterface::class);
        $feed->getCode()->willReturn('google');

        return $feed->reveal();
    }

    /**
     * @test
     */
    public function it_generates_each_enabled_feed_across_its_contexts(): void
    {
        $feed = $this->feed();

        $repository = $this->prophesize(RepositoryInterface::class);
        $repository->findBy(['enabled' => true])->willReturn([$feed]);

        $contextFactory = $this->prophesize(ContextFactoryInterface::class);
        $contextFactory->create($feed)->willReturn([new FeedContext(null, 'en_US', 'USD')]);

        $generator = $this->prophesize(FeedGeneratorInterface::class);
        $generator->generate($feed, Argument::type(FeedContext::class))->willReturn(new GenerationResult('google/en_us_usd.xml', 5, 1));

        $tester = new CommandTester(new ProcessFeedCommand($repository->reveal(), $contextFactory->reveal(), $generator->reveal()));
        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('google', $tester->getDisplay());
        self::assertStringContainsString('5 items', $tester->getDisplay());
    }

    /**
     * @test
     */
    public function it_generates_only_the_requested_feed(): void
    {
        $feed = $this->feed();

        $repository = $this->prophesize(RepositoryInterface::class);
        $repository->findOneBy(['code' => 'google'])->willReturn($feed);

        $contextFactory = $this->prophesize(ContextFactoryInterface::class);
        $contextFactory->create($feed)->willReturn([new FeedContext()]);

        $generator = $this->prophesize(FeedGeneratorInterface::class);
        $generator->generate($feed, Argument::type(FeedContext::class))->willReturn(new GenerationResult('google/default.xml', 2, 0));

        $tester = new CommandTester(new ProcessFeedCommand($repository->reveal(), $contextFactory->reveal(), $generator->reveal()));
        $exitCode = $tester->execute(['--feed' => 'google']);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    /**
     * @test
     */
    public function it_warns_when_there_are_no_feeds_to_process(): void
    {
        $repository = $this->prophesize(RepositoryInterface::class);
        $repository->findBy(['enabled' => true])->willReturn([]);

        $contextFactory = $this->prophesize(ContextFactoryInterface::class);
        $generator = $this->prophesize(FeedGeneratorInterface::class);

        $tester = new CommandTester(new ProcessFeedCommand($repository->reveal(), $contextFactory->reveal(), $generator->reveal()));
        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('No feeds', $tester->getDisplay());
    }
}
