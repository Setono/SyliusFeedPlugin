<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Event;

use Setono\SyliusFeedPlugin\Item\FeedItem;

/**
 * Dispatched after a {@see FeedItem} is fully mapped and before post-filters/validation/write
 * (§6.5). Listeners may enrich, override, or veto the item (via `$item->skip()`); because it
 * carries the concrete item, listeners can type-check the subclass (e.g. GoogleShoppingItem).
 */
final class FeedItemBuiltEvent
{
    public function __construct(public readonly FeedItem $item)
    {
    }
}
