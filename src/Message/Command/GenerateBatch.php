<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

use Setono\DoctrineORMBatcher\Batch\BatchInterface;

final class GenerateBatch implements CommandInterface
{
    /** @var int */
    private $feedId;

    /** @var BatchInterface */
    private $batch;

    public function __construct(int $feedId, BatchInterface $batch)
    {
        $this->feedId = $feedId;
        $this->batch = $batch;
    }

    public function getFeedId(): int
    {
        return $this->feedId;
    }

    public function getBatch(): BatchInterface
    {
        return $this->batch;
    }
}
