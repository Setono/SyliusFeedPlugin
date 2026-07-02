<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Writer;

use Setono\SyliusFeedPlugin\Context\FeedContext;

/**
 * The supplemental manifest strategy (§12), used by Google RSS: when a context is split, it emits a
 * small XML manifest that lists the part filenames so a destination can discover every part of the
 * feed from one canonical entry point. The manifest is written to the un-suffixed canonical path
 * ({code}/{contextKey}.{ext}) alongside the numbered parts ({code}/{contextKey}-1.{ext}, …), and
 * each `<part>` element carries a part's filename (basename, gzip suffix included when enabled).
 *
 * Shape:
 *
 *     <?xml version="1.0" encoding="UTF-8"?>
 *     <manifest context="web_en_us_usd" parts="3">
 *       <part>web_en_us_usd-1.xml</part>
 *       <part>web_en_us_usd-2.xml</part>
 *       <part>web_en_us_usd-3.xml</part>
 *     </manifest>
 */
final class SupplementalSplitManifest implements SplitManifestInterface
{
    public const TYPE = 'supplemental';

    public function getType(): string
    {
        return self::TYPE;
    }

    public function writeManifest($manifestStream, array $partPaths, FeedContext $context, WriterConfigInterface $config): void
    {
        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('manifest');
        $writer->writeAttribute('context', $context->key());
        $writer->writeAttribute('parts', (string) count($partPaths));

        foreach ($partPaths as $partPath) {
            $writer->writeElement('part', basename($partPath));
        }

        $writer->endElement();
        $writer->endDocument();

        fwrite($manifestStream, $writer->outputMemory());
    }
}
