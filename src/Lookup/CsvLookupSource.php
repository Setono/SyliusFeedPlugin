<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Lookup;

use League\Csv\Reader;
use League\Flysystem\FilesystemOperator;
use Setono\SyliusFeedPlugin\Model\LookupTableInterface;

/**
 * Imports lookup rows from an uploaded CSV file (§10.1): `sourceConfig.path` is read from the feed
 * filesystem and parsed with its header row as the column names.
 */
final class CsvLookupSource implements LookupSourceInterface
{
    public function __construct(private readonly FilesystemOperator $filesystem)
    {
    }

    public function getType(): string
    {
        return LookupTableInterface::SOURCE_TYPE_CSV;
    }

    public function fetch(array $sourceConfig): iterable
    {
        $path = $sourceConfig['path'] ?? null;
        if (!is_string($path) || '' === $path) {
            return;
        }

        $reader = Reader::createFromStream($this->filesystem->readStream($path));
        $reader->setHeaderOffset(0);

        foreach ($reader->getRecords() as $record) {
            yield LookupRow::normalize($record);
        }
    }
}
