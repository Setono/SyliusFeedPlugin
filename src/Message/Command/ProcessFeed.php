<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

final class ProcessFeed implements CommandInterface
{
    public function __construct(private readonly int $feedId)
    {
    }

    public function getFeedId(): int
    {
        return $this->feedId;
    }
}
