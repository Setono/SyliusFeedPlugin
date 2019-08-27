<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

final class FinishFeedGeneration implements CommandInterface
{
    /** @var int */
    private $feedId;

    public function __construct(int $feedId)
    {
        $this->feedId = $feedId;
    }

    public function getFeedId(): int
    {
        return $this->feedId;
    }
}
