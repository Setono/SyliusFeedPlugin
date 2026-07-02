<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Writer;

/**
 * Structural configuration for the {@see CsvWriter} (§7): the delimiter/enclosure, whether to emit
 * a UTF-8 BOM, and the header — the union of output fields across the feed's sources. The header is
 * computed by the generator (it depends on the feed's mappings, not the static format preset) and
 * injected via {@see withHeader()}.
 */
final class CsvWriterConfig implements WriterConfigInterface
{
    /**
     * @param list<string> $header
     */
    public function __construct(
        public readonly string $delimiter = ',',
        public readonly string $enclosure = '"',
        public readonly array $header = [],
        public readonly bool $bom = false,
    ) {
    }

    /**
     * CSV has no preamble, so feed metadata is ignored.
     */
    public function withFeedMetadata(array $metadata): self
    {
        return $this;
    }

    /**
     * @param list<string> $header
     */
    public function withHeader(array $header): self
    {
        return new self($this->delimiter, $this->enclosure, $header, $this->bom);
    }
}
