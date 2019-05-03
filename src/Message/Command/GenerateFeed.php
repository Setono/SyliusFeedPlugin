<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

final class GenerateFeed
{
    /**
     * @var int
     */
    private $feedId;

    public function __construct(int $feedId)
    {
        $this->feedId = $feedId;
    }

    /**
     * @return int
     */
    public function getFeedId(): int
    {
        return $this->feedId;
    }
}
