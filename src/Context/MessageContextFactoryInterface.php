<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Context;

/**
 * Rebuilds a {@see FeedContext} from the transport-safe dimension codes carried on a generation
 * message (§6.2). Shared by every generation handler so a context is reconstructed identically.
 */
interface MessageContextFactoryInterface
{
    public function create(?string $channelCode, ?string $locale, ?string $currencyCode): FeedContext;
}
