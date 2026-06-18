<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MappingPreset;

use Setono\SyliusFeedPlugin\Mapping\FieldMapping;

/**
 * Binds a (feedType, target) to a chosen format + default FieldMapping rows (§5). What the admin
 * "target" picker lists (Google Shopping, Meta, Partner-ads, …); adding a channel = one new
 * preset, no FeedType/writer edits.
 *
 * Collected into the MappingPresetRegistry via the `setono_sylius_feed.mapping_preset` tag.
 */
interface MappingPresetInterface
{
    /**
     * e.g. "google_shopping".
     */
    public function getCode(): string;

    /**
     * e.g. "Google Shopping" (admin "target" picker).
     */
    public function getLabel(): string;

    /**
     * Whether this preset applies to the given feed type code (FeedType::getCode()).
     */
    public function supports(string $feedType): bool;

    /**
     * The format it seeds, e.g. "google_rss".
     */
    public function getFormat(): string;

    /**
     * Output←source rows + transforms + conditions + requiresInput flags.
     *
     * @return list<FieldMapping>
     */
    public function getMapping(): array;
}
