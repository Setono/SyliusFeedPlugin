<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Format;

use Setono\SyliusFeedPlugin\Writer\CsvWriter;
use Setono\SyliusFeedPlugin\Writer\CsvWriterConfig;
use Setono\SyliusFeedPlugin\Writer\WriterConfigInterface;

/**
 * The generic comma-delimited CSV format (§7): the `csv` writer family with a default
 * comma/double-quote dialect. Destination-specific CSV dialects (Meta, …) are mapping presets over
 * this format; the header is the union of the feed's output fields, injected by the generator.
 */
final class CsvFormat implements FormatInterface
{
    public function getCode(): string
    {
        return 'csv';
    }

    public function getWriter(): string
    {
        return CsvWriter::FORMAT;
    }

    public function getConfig(): WriterConfigInterface
    {
        return new CsvWriterConfig();
    }

    public function getRequiredFields(): array
    {
        return [];
    }
}
