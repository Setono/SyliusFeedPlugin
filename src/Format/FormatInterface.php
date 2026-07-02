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

    /**
     * The type of {@see \Setono\SyliusFeedPlugin\Writer\SplitManifestInterface} strategy that ties a
     * split context's parts together (§12). Returns `supplemental` for destinations that expect a
     * primary feed referencing supplemental parts (e.g. Google), and `none` for formats whose parts
     * are self-describing complete documents and need no manifest.
     */
    public function getSplitManifest(): string;

    /**
     * The default size limit above which a context's output is split into multiple parts (§12); an
     * unset key means that dimension is not limited, and an empty array means the format never splits
     * by default. The effective limit is this default with the feed's `formatConfig['split']`
     * overrides merged over it, so an admin can lower it (e.g. to force splitting for testing).
     *
     * @return array{maxItems?: int, maxBytes?: int}
     */
    public function getSplitLimit(): array;
}
