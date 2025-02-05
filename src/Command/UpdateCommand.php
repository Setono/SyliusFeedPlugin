<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Command;

use Setono\SyliusFeedPlugin\Message\Command\StartFeedUpdate;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'setono:sylius-feed:update',
    description: 'Updates a single feed or all feeds',
)]
final class UpdateCommand extends Command
{
    public function __construct(private readonly MessageBusInterface $commandBus)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->commandBus->dispatch(new StartFeedUpdate());

        return 0;
    }
}
