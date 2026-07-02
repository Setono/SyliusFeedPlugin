<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Writer;

use Setono\SyliusFeedPlugin\Context\FeedContext;

/**
 * Ties a split context's parts together (§12): when a context's output is split into multiple
 * parts, the format's chosen manifest strategy emits an optional manifest/primary file describing
 * the set. Concrete strategies are collected into the {@see SplitManifestRegistryInterface} via the
 * `setono_sylius_feed.split_manifest` tag and selected per format by
 * {@see \Setono\SyliusFeedPlugin\Format\FormatInterface::getSplitManifest()}.
 */
interface SplitManifestInterface
{
    /**
     * The strategy type, e.g. "none" or "supplemental" — the key it is registered under.
     */
    public function getType(): string;

    /**
     * Write the manifest for the given parts to $manifestStream. A strategy that emits nothing
     * (parts are self-describing) leaves the stream empty and the generator writes no manifest file.
     *
     * @param resource     $manifestStream
     * @param list<string> $partPaths      storage-relative paths of the produced parts, in order
     */
    public function writeManifest($manifestStream, array $partPaths, FeedContext $context, WriterConfigInterface $config): void;
}
