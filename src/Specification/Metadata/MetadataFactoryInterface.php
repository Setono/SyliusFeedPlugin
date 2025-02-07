<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Specification\Metadata;

use Setono\SyliusFeedPlugin\Specification\Specification;

interface MetadataFactoryInterface
{
    public function getMetadataFor(Specification $specification): Metadata;
}
