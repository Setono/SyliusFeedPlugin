<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Command;

use Setono\SyliusFeedPlugin\Processor\FeedProcessorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessFeedsCommand extends Command
{
    protected static $defaultName = 'setono:sylius-feed:process';

    private FeedProcessorInterface $feedProcessor;

    public function __construct(FeedProcessorInterface $feedProcessor)
    {
        parent::__construct();

        $this->feedProcessor = $feedProcessor;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Processes all enabled feeds')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->feedProcessor->setLogger(new ConsoleLogger($output));
        $this->feedProcessor->process();

        return 0;
    }
}
