<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Lookup;

use League\Csv\Reader;
use Setono\SyliusFeedPlugin\Model\LookupTableInterface;

/**
 * Imports lookup rows from CSV/TSV at a URL (§10.1). This covers a published-to-web Google Sheet
 * (its `…/export?format=csv` link is plain CSV — no Google-specific code, no auth). A download
 * failure throws, so the refresher can keep the last-good rows.
 */
final class UrlLookupSource implements LookupSourceInterface
{
    public function getType(): string
    {
        return LookupTableInterface::SOURCE_TYPE_URL;
    }

    public function fetch(array $sourceConfig): iterable
    {
        $url = $sourceConfig['url'] ?? null;
        if (!is_string($url) || '' === $url) {
            return;
        }

        $content = @file_get_contents($url);
        if (false === $content) {
            throw new \RuntimeException(sprintf('Could not download the lookup CSV from "%s"', $url));
        }

        $reader = Reader::createFromString($content);
        $reader->setHeaderOffset(0);

        foreach ($reader->getRecords() as $record) {
            yield LookupRow::normalize($record);
        }
    }
}
