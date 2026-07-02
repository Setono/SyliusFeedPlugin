<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Delivery;

use Setono\SyliusFeedPlugin\Model\FeedContextResultInterface;
use Setono\SyliusFeedPlugin\Model\FeedInterface;

interface DeliveryServiceInterface
{
    /**
     * Pushes a promoted context's canonical file set to every delivery target whose match selects it
     * (§12). Best-effort and isolated: each push is wrapped so a failing target/file records an error
     * on the result and never prevents the other files, targets or contexts from being delivered —
     * this method never throws.
     *
     * @param list<string> $paths the canonical (already promoted) file set for the context: parts + manifest
     */
    public function deliver(FeedInterface $feed, FeedContextResultInterface $result, array $paths): void;
}
