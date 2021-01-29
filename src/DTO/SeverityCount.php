<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DTO;

final class SeverityCount
{
    private string $severity;

    private int $count;

    public function __construct(string $severity, int $count)
    {
        $this->severity = $severity;
        $this->count = $count;
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
