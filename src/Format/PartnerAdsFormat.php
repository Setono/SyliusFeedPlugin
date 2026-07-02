<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Format;

use Setono\SyliusFeedPlugin\Writer\WriterConfigInterface;
use Setono\SyliusFeedPlugin\Writer\XmlWriterConfig;

/**
 * The Partner-ads product feed format (§7): a `produkter` root with `produkt` item elements —
 * rendered by the XML writer.
 */
final class PartnerAdsFormat implements FormatInterface
{
    public function getCode(): string
    {
        return 'partner_ads';
    }

    public function getWriter(): string
    {
        return 'xml';
    }

    public function getConfig(): WriterConfigInterface
    {
        return new XmlWriterConfig(rootElement: 'produkter', itemElement: 'produkt');
    }

    public function getRequiredFields(): array
    {
        return [];
    }

    public function getItemValidationGroups(): array
    {
        return [];
    }
}
