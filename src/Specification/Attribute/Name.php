<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Specification\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Name
{
    public function __construct(public readonly string $name)
    {
    }
}
