<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Format;

/**
 * The Google Merchant RSS 2.0 format (§7): an `rss version="2.0"` root declaring the `g`
 * namespace, a `channel` wrapper, and `item` elements — rendered by the XML writer.
 */
final class GoogleRssFormat implements FormatInterface
{
    public function getCode(): string
    {
        return 'google_rss';
    }

    public function getWriter(): string
    {
        return 'xml';
    }

    public function getConfig(): array
    {
        return [
            'rootElement' => 'rss',
            'rootAttributes' => ['version' => '2.0'],
            'namespaces' => ['g' => 'http://base.google.com/ns/1.0'],
            'wrapperElement' => 'channel',
            'itemElement' => 'item',
            'requiredFields' => ['g:id', 'g:title', 'g:description', 'g:link', 'g:image_link', 'g:availability', 'g:price'],
        ];
    }
}
