<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Writer;

use Setono\SyliusFeedPlugin\Registry\RegistryInterface;

/**
 * @extends RegistryInterface<SplitManifestInterface>
 */
interface SplitManifestRegistryInterface extends RegistryInterface
{
    public function get(string $type): SplitManifestInterface;
}
