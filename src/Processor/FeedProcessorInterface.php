<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Processor;

use Psr\Log\LoggerAwareInterface;

interface FeedProcessorInterface extends LoggerAwareInterface
{
    /**
     * Processes all enabled feeds
     */
    public function process(): void;
}
