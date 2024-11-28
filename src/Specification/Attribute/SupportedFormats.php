<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Specification\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class SupportedFormats
{
    public function __construct(
        /** @var non-empty-list<string> $supportedFormats */
        public readonly array $supportedFormats,
    ) {
    }
}
