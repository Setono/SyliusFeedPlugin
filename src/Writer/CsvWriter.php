<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Writer;

use League\Csv\ByteSequence;
use League\Csv\Writer;
use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Webmozart\Assert\Assert;

/**
 * The "csv" writer family (§7): streams delimited text with `league/csv`. A single header row (the
 * union of the feed's output fields) is written in the preamble, then one row per item where each
 * cell is looked up by header column — a missing field becomes an empty cell, a list is joined,
 * so heterogeneous multi-source rows line up under one header. league/csv handles quoting/escaping.
 */
final class CsvWriter implements FeedWriterInterface
{
    public const FORMAT = 'csv';

    private ?Writer $writer = null;

    /** @var resource|null */
    private $stream;

    /** @var list<string> */
    private array $header = [];

    private bool $bom = false;

    public function getFormat(): string
    {
        return self::FORMAT;
    }

    public function open($stream, FeedContext $context, WriterConfigInterface $config): void
    {
        Assert::isInstanceOf($config, CsvWriterConfig::class);

        $writer = Writer::createFromStream($stream);
        $writer->setDelimiter($config->delimiter);
        $writer->setEnclosure($config->enclosure);

        $this->writer = $writer;
        $this->stream = $stream;
        $this->header = $config->header;
        $this->bom = $config->bom;
    }

    public function writePreamble(): void
    {
        // The BOM belongs to the preamble (start of the file), not to open(): a body-only chunk skips
        // the preamble and so must not emit it. league/csv only prepends the BOM via its own output
        // methods, not when streaming records to an external stream — so write it directly.
        if ($this->bom && null !== $this->stream) {
            fwrite($this->stream, ByteSequence::BOM_UTF8);
        }

        if ([] !== $this->header) {
            $this->getWriter()->insertOne($this->header);
        }
    }

    public function writeItem(FeedItem $item): void
    {
        $row = [];
        foreach ($this->header as $column) {
            $row[] = $this->toCell($item->get($column));
        }

        $this->getWriter()->insertOne($row);
    }

    public function writeEpilogue(): void
    {
        // CSV has no epilogue.
    }

    public function close(): void
    {
        // The generator owns the underlying stream; just drop the references.
        $this->writer = null;
        $this->stream = null;
    }

    private function toCell(mixed $value): string
    {
        if (is_array($value)) {
            return implode(',', array_map($this->scalarToString(...), $value));
        }

        return $this->scalarToString($value);
    }

    private function scalarToString(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return is_scalar($value) ? (string) $value : '';
    }

    private function getWriter(): Writer
    {
        Assert::notNull($this->writer, 'The CSV writer has not been opened');

        return $this->writer;
    }
}
