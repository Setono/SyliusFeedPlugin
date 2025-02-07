<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Specification\Metadata;

class Metadata
{
    public function __construct(
        public readonly string $name,
        public readonly string $format,
    ) {
    }
}
