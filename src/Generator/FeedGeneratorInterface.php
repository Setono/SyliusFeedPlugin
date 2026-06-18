<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Model\FeedInterface;

interface FeedGeneratorInterface
{
    /**
     * Generates one context's feed file, streaming it to storage (§6).
     */
    public function generate(FeedInterface $feed, FeedContext $context): GenerationResult;
}
