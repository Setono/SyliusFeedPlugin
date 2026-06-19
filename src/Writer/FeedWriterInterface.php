<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Writer;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\FeedItem;

/**
 * Serializes mapped items, streaming (§5). Two writer families — "xml" and "csv"; concrete
 * formats (google_rss, generic_xml, …) are presets resolved to a writer + config by the
 * FormatRegistry (§7).
 *
 * Collected into the FeedWriterRegistry via the `setono_sylius_feed.writer` tag.
 */
interface FeedWriterInterface
{
    /**
     * The writer family, e.g. "xml" or "csv".
     */
    public function getFormat(): string;

    /**
     * @param resource $stream
     */
    public function open($stream, FeedContext $context, WriterConfigInterface $config): void;

    /**
     * Doc prolog + root/channel header (CSV: header row). Skipped in body-only/chunk mode.
     */
    public function writePreamble(): void;

    /**
     * Serialize from $item->all() (ordered bag); writers stay generic — never switch on the
     * item subclass.
     */
    public function writeItem(FeedItem $item): void;

    /**
     * Root/channel close. Skipped in body-only/chunk mode.
     */
    public function writeEpilogue(): void;

    public function close(): void;
}
