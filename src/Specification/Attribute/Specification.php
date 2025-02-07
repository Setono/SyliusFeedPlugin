<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Specification\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Specification
{
    public readonly string $format;

    public function __construct(
        public readonly string $name,
        string $format,
    ) {
        $this->format = strtolower($format);
    }
}
