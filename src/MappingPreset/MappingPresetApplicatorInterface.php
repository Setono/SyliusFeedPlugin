<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\MappingPreset;

use Setono\SyliusFeedPlugin\Model\FeedInterface;

/**
 * Seeds a feed from a mapping preset — the "target" picker (§7, §10): sets the format and adds a
 * source whose FeedField rows are populated from the preset's default mapping, so the admin starts
 * from a working Google/Meta/… mapping rather than a blank grid. Rows the preset cannot resolve
 * from core Sylius are carried over with their `requiresInput` flag for the admin to complete.
 */
interface MappingPresetApplicatorInterface
{
    public function apply(FeedInterface $feed, MappingPresetInterface $preset): void;
}
