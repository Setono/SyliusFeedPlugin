<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Command;

use Setono\SyliusFeedPlugin\Audit\AuditReport;
use Setono\SyliusFeedPlugin\Audit\FeedAuditServiceInterface;
use Setono\SyliusFeedPlugin\Context\ContextFactoryInterface;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Message\Command\ProcessFeed;
use Setono\SyliusFeedPlugin\Model\FeedInterface;
use Setono\SyliusFeedPlugin\Preview\PreviewServiceInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'setono:feed:process', description: 'Dispatch feed generation (all enabled feeds, or one with --feed=CODE)')]
final class ProcessFeedCommand extends Command
{
    private const DEFAULT_PREVIEW_LIMIT = 50;

    private const SAMPLE_ROWS = 5;

    private const SAMPLE_FIELDS = 6;

    public function __construct(
        private readonly RepositoryInterface $feedRepository,
        private readonly MessageBusInterface $commandBus,
        private readonly ContextFactoryInterface $contextFactory,
        private readonly PreviewServiceInterface $previewService,
        private readonly FeedAuditServiceInterface $auditService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('feed', null, InputOption::VALUE_REQUIRED, 'Process only the feed with this code')
            ->addOption('preview', null, InputOption::VALUE_OPTIONAL, 'Dry-run: print the funnel + a few sample rows instead of generating (optionally set the sample size, default ' . self::DEFAULT_PREVIEW_LIMIT . ')', false)
            ->addOption('audit', null, InputOption::VALUE_NONE, 'Dry-run: also print fill rates and soft warnings (implies --preview)')
        ;
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

        $audit = true === $input->getOption('audit');
        $previewOption = $input->getOption('preview');

        if ($audit || false !== $previewOption) {
            $limit = is_numeric($previewOption) ? max(1, (int) $previewOption) : self::DEFAULT_PREVIEW_LIMIT;

            foreach ($feeds as $feed) {
                if ($feed instanceof FeedInterface) {
                    $this->previewFeed($io, $feed, $limit, $audit);
                }
            }

            return Command::SUCCESS;
        }

        foreach ($feeds as $feed) {
            if (!$feed instanceof FeedInterface) {
                continue;
            }

            $this->commandBus->dispatch(new ProcessFeed($feed));

            $io->writeln(sprintf('<info>%s</info>: dispatched for processing', (string) $feed->getCode()));
        }

        return Command::SUCCESS;
    }

    private function previewFeed(SymfonyStyle $io, FeedInterface $feed, int $limit, bool $audit): void
    {
        $contexts = $this->contextFactory->create($feed);
        $context = $contexts[0] ?? new FeedContext();

        $result = $this->previewService->preview($feed, $context, $limit);
        $funnel = $result->funnel;

        $io->section(sprintf('%s (%s)', (string) $feed->getCode(), $context->key()));
        $io->table(
            ['Stage', 'Count'],
            [
                ['Sampled', (string) $funnel->source],
                ['After pre-filters', (string) $funnel->afterPreFilters],
                ['After mapping & validation', (string) $funnel->afterMappingValidation],
                ['After post-filters', (string) $funnel->afterPostFilters],
                ['Included', (string) $funnel->included],
            ],
        );

        $includedSample = array_slice($result->included, 0, self::SAMPLE_ROWS);
        if ([] !== $includedSample) {
            $io->writeln('<info>Included sample:</info>');
            foreach ($includedSample as $bag) {
                $io->writeln(' - ' . $this->summarizeBag($bag));
            }
            $io->newLine();
        }

        $excludedSample = array_slice($result->excluded, 0, self::SAMPLE_ROWS);
        if ([] !== $excludedSample) {
            $io->writeln('<comment>Excluded sample:</comment>');
            foreach ($excludedSample as $row) {
                $io->writeln(sprintf(' - %s: %s', $row['item'] ?? '(no id)', $row['reason']));
            }
            $io->newLine();
        }

        if ($audit) {
            $this->printAudit($io, $this->auditService->audit($result));
        }
    }

    private function printAudit(SymfonyStyle $io, AuditReport $report): void
    {
        if ([] !== $report->fillRates) {
            $rows = [];
            foreach ($report->fillRates as $field => $rate) {
                $rows[] = [$field, sprintf('%.1f%%', $rate * 100)];
            }
            $io->writeln('<info>Fill rates:</info>');
            $io->table(['Field', 'Fill rate'], $rows);
        }

        if ([] !== $report->warnings) {
            $rows = [];
            foreach ($report->warnings as $warning) {
                $rows[] = [$warning['type'], $warning['field'], (string) $warning['count']];
            }
            $io->writeln('<comment>Soft warnings:</comment>');
            $io->table(['Type', 'Field', 'Count'], $rows);
        }
    }

    /**
     * @param array<string, mixed> $bag
     */
    private function summarizeBag(array $bag): string
    {
        $parts = [];
        foreach ($bag as $key => $value) {
            $parts[] = sprintf('%s=%s', $key, $this->stringifyValue($value));
        }

        return implode(', ', array_slice($parts, 0, self::SAMPLE_FIELDS));
    }

    private function stringifyValue(mixed $value): string
    {
        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return implode('|', array_map(
                static fn (mixed $item): string => is_scalar($item) ? (string) $item : get_debug_type($item),
                $value,
            ));
        }

        return get_debug_type($value);
    }
}
