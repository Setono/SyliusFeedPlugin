<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Processor;

interface FeedProcessorInterface
{
    /**
     * Processes all enabled feeds
     */
    public function process(): void;
}
