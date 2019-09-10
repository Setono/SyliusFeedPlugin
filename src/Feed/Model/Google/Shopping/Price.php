<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping;

use Webmozart\Assert\Assert;

final class Price
{
    /** @var int */
    private $amount;

    /** @var string */
    private $currency;

    /**
     * @param object|string $currency
     */
    public function __construct(int $amount, $currency)
    {
        Assert::greaterThanEq($amount, 0);

        $this->amount = $amount;
        $this->currency = (string) $currency;
    }

    public function __toString(): string
    {
        return round($this->amount / 100, 2) . ' ' . $this->currency;
    }
}
