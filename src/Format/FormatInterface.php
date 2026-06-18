<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Format;

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
     * Structural defaults merged under the feed's own `formatConfig`.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array;
}
