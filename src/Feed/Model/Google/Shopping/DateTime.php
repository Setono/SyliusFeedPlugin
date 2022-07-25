<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping;

use DateTime as BaseDateTime;
use JsonSerializable;

final class DateTime extends BaseDateTime implements JsonSerializable
{
    public function __toString(): string
    {
        return $this->format('Y-m-d\TH:iO');
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }
}
