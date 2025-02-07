<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Specification\Vendor\Google\Shopping;

final class Money implements \Stringable
{
    public function __construct(
        public readonly string $currency,
        public readonly int $amount,
    ) {
    }

    public function __toString(): string
    {
        return sprintf('%01.2f %s', $this->amount, $this->currency);
    }
}
