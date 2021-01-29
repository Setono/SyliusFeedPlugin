<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping;

final class DateRange
{
    private DateTime $start;

    private DateTime $end;

    public function __construct(DateTime $start, DateTime $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function __toString(): string
    {
        return $this->start . '/' . $this->end;
    }
}
