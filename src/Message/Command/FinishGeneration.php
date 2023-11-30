<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

class FinishGeneration implements CommandInterface
{
    private int $feedId;

    public function __construct(int $feedId)
    {
        $this->feedId = $feedId;
    }

    public function getFeedId(): int
    {
        return $this->feedId;
    }
}
