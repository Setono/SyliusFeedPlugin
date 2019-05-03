<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Command;

use Setono\SyliusFeedPlugin\Processor\FeedProcessorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ProcessFeedsCommand extends Command
{
    /**
     * @var FeedProcessorInterface
     */
    private $feedProcessor;

    public function __construct(FeedProcessorInterface $feedProcessor)
    {
        parent::__construct();

        $this->feedProcessor = $feedProcessor;
    }

    protected function configure(): void
    {
        $this
            ->setName('setono_sylius_feed:process')
            ->setDescription('Processes all enabled feeds')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->feedProcessor->process();
    }
}
