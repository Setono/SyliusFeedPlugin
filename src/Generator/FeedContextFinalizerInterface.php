<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Generator;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Model\FeedInterface;

/**
 * The shared per-context finalize step (§6.3, §6.6) both the inline path and the fan-out path run
 * once a context's canonical file exists: record the {@see \Setono\SyliusFeedPlugin\Model\FeedContextResultInterface}
 * candidate, run it through the publish gate, count the context as completed and, when it is the last
 * context of the run, complete the feed. Factored out so the two paths behave identically.
 *
 * The passed feed MUST be a managed entity (the generator clears the entity manager while streaming,
 * so callers re-fetch it first).
 */
interface FeedContextFinalizerInterface
{
    public function finalize(FeedInterface $feed, FeedContext $context, GenerationResult $result): void;
}
