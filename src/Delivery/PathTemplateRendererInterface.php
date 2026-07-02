<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Delivery;

interface PathTemplateRendererInterface
{
    /**
     * Renders a delivery target's path template (§12) by replacing the placeholders {channel},
     * {locale}, {currency}, {contextKey}, {ext} and {part}. Absent dimensions and part render as an
     * empty string.
     */
    public function render(
        string $template,
        ?string $channelCode,
        ?string $localeCode,
        ?string $currencyCode,
        string $contextKey,
        string $ext,
        ?string $part = null,
    ): string;
}
