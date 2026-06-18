<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Command;

use Setono\SyliusFeedPlugin\Context\ContextFactoryInterface;
use Setono\SyliusFeedPlugin\Generator\FeedGeneratorInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'setono:feed:process', description: 'Generate the feeds (all enabled feeds, or one with --feed=CODE)')]
final class ProcessFeedCommand extends Command
{
    public function __construct(
        private readonly RepositoryInterface $feedRepository,
        private readonly ContextFactoryInterface $contextFactory,
        private readonly FeedGeneratorInterface $feedGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('feed', null, InputOption::VALUE_REQUIRED, 'Generate only the feed with this code');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string|null $code */
        $code = $input->getOption('feed');

        $feeds = null !== $code
            ? array_filter([$this->feedRepository->findOneBy(['code' => $code])])
            : $this->feedRepository->findBy(['enabled' => true]);

        if ([] === $feeds) {
            $io->warning('No feeds to process');

            return Command::SUCCESS;
        }

        foreach ($feeds as $feed) {
            if (!$feed instanceof FeedInterface) {
                continue;
            }

            foreach ($this->contextFactory->create($feed) as $context) {
                $result = $this->feedGenerator->generate($feed, $context);

                $io->writeln(sprintf(
                    '<info>%s</info> [%s]: %d items (%d excluded) → %s',
                    (string) $feed->getCode(),
                    $context->key(),
                    $result->itemCount,
                    $result->excludedCount,
                    $result->path,
                ));
            }
        }

        return Command::SUCCESS;
    }
}
