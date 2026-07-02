<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Delivery;

final class PathTemplateRenderer implements PathTemplateRendererInterface
{
    public function render(
        string $template,
        ?string $channelCode,
        ?string $localeCode,
        ?string $currencyCode,
        string $contextKey,
        string $ext,
        ?string $part = null,
    ): string {
        return strtr($template, [
            '{channel}' => $channelCode ?? '',
            '{locale}' => $localeCode ?? '',
            '{currency}' => $currencyCode ?? '',
            '{contextKey}' => $contextKey,
            '{ext}' => $ext,
            '{part}' => $part ?? '',
        ]);
    }
}
