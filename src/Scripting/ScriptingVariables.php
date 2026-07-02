<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Scripting;

use Setono\SyliusFeedPlugin\Item\FeedItem;

/**
 * Builds the variable set shared by the two scripting surfaces — `expression` and `twig` (§10):
 * `value` (the value flowing through the chain), `entity`, `channel`, `locale`, `currency`, and
 * `fields` (the output bag mapped so far). The `lookup(table, key, column)` function is registered
 * separately on each engine.
 */
final class ScriptingVariables
{
    /**
     * @return array<string, mixed>
     */
    public static function for(FeedItem $item, mixed $value): array
    {
        $context = $item->getContext();

        return [
            'value' => $value,
            'entity' => $item->getEntity(),
            'channel' => $context->getChannel(),
            'locale' => $context->getLocale(),
            'currency' => $context->getCurrencyCode(),
            'fields' => $item->all(),
        ];
    }
}
