<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\DTO;

final class SeverityCount
{
    /** @var string */
    private $severity;

    /** @var int */
    private $count;

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
