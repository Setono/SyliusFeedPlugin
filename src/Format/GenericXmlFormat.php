<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Format;

use Setono\SyliusFeedPlugin\Writer\WriterConfigInterface;
use Setono\SyliusFeedPlugin\Writer\XmlWriterConfig;

/**
 * A generic, destination-agnostic XML format (§7): a `feed` root with `item` elements — rendered
 * by the XML writer. Useful as a starting point for custom destinations that don't match one of
 * the named presets.
 */
final class GenericXmlFormat implements FormatInterface
{
    public function getCode(): string
    {
        return 'generic_xml';
    }

    public function getWriter(): string
    {
        return 'xml';
    }

    public function getConfig(): WriterConfigInterface
    {
        return new XmlWriterConfig(rootElement: 'feed', itemElement: 'item');
    }

    public function getRequiredFields(): array
    {
        return [];
    }

    public function getItemValidationGroups(): array
    {
        return [];
    }

    public function getSplitManifest(): string
    {
        // Each part is a complete, self-describing XML document.
        return 'none';
    }

    public function getSplitLimit(): array
    {
        return [];
    }
}
