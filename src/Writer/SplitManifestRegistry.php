<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Writer;

use Setono\SyliusFeedPlugin\Registry\Registry;

/**
 * @extends Registry<SplitManifestInterface>
 */
final class SplitManifestRegistry extends Registry implements SplitManifestRegistryInterface
{
    public function get(string $type): SplitManifestInterface
    {
        return $this->getByKey($type);
    }

    /**
     * @param SplitManifestInterface $item
     */
    protected function getKey(object $item): string
    {
        return $item->getType();
    }
}
