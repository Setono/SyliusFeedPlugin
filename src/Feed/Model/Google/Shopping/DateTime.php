<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping;

use DateTime as BaseDateTime;

final class DateTime extends BaseDateTime
{
    public function __toString(): string
    {
        return $this->format('Y-m-d\TH:iO');
    }
}
