<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DTO;

final class SeverityCount
{
    public function __construct(private readonly string $severity, private readonly int $count)
    {
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
