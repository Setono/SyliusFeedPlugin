<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message\Command;

use Setono\SyliusFeedPlugin\Model\FeedUpdateBatchInterface;

/**
 * @internal
 */
final class ProcessFeedUpdateBatch implements AsyncCommandInterface
{
    public readonly int $feedUpdateBatch;

    public function __construct(int|FeedUpdateBatchInterface $feedUpdateBatch)
    {
        if ($feedUpdateBatch instanceof FeedUpdateBatchInterface) {
            $feedUpdateBatch = (int) $feedUpdateBatch->getId();
        }

        $this->feedUpdateBatch = $feedUpdateBatch;
    }
}
