<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;

interface FeedContextResultRecorderInterface
{
    /**
     * Persist a {@see FeedContextResultInterface} capturing the outcome of one context's generation
     * run (§11), so the excluded-item report and the publish gate have a durable record.
     */
    public function record(FeedInterface $feed, string $contextKey, GenerationResult $result): FeedContextResultInterface;
}
