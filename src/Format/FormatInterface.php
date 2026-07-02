<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Format;

use Setono\SyliusFeedPlugin\Writer\WriterConfigInterface;

/**
 * A named structural format (§7): binds a format code (e.g. `google_rss`) to a writer family
 * (`xml`/`csv`) and the structural config that writer needs (root/namespaces/wrapper/item, or
 * delimiter). Formats are presets over the two writer classes — not writer subclasses.
 *
 * Collected into the FormatRegistry via the `setono_sylius_feed.format` tag.
 */
interface FormatInterface
{
    /**
     * e.g. "google_rss", "generic_xml", "csv".
     */
    public function getCode(): string;

    /**
     * The writer family that renders this format, e.g. "xml" or "csv".
     */
    public function getWriter(): string;

    /**
     * The typed structural configuration for {@see getWriter()}'s writer family.
     */
    public function getConfig(): WriterConfigInterface;

    /**
     * Output fields an item must carry (non-empty) to be included in the feed; an empty list means
     * no requirement. This is a validation concern, kept separate from the structural writer config.
     *
     * @return list<string>
     */
    public function getRequiredFields(): array;

    /**
     * The Symfony validation groups to run the typed per-item constraints under for this format
     * (§11). A format that carries typed-item constraints (e.g. Google Shopping) returns the group(s)
     * that activate them; a format that only relies on the generic required-field check returns an
     * empty list, so a typed item (like GoogleShoppingItem) rendered under a non-matching format is
     * not spuriously rejected.
     *
     * @return list<string>
     */
    public function getItemValidationGroups(): array;
}
