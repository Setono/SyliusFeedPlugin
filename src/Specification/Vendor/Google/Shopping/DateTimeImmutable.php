<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Specification\Vendor\Google\Shopping;

final class DateTimeImmutable extends \DateTimeImmutable implements \Stringable
{
    public function __toString(): string
    {
        return $this->format(\DATE_ATOM);
    }
}
