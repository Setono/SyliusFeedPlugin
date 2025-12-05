<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping;

use JsonSerializable;

final class DateRange implements JsonSerializable, \Stringable
{
    public function __construct(private readonly DateTime $start, private readonly DateTime $end)
    {
    }

    public function __toString(): string
    {
        return $this->start . '/' . $this->end;
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }
}
