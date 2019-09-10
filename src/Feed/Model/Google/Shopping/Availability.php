<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping;

use Setono\SyliusFeedPlugin\Feed\Model\ConstantClassTrait;
use Webmozart\Assert\Assert;

final class Availability
{
    use ConstantClassTrait;

    private const IN_STOCK = 'in stock';

    private const OUT_OF_STOCK = 'out of stock';

    private const PREORDER = 'preorder';

    public static function inStock(): self
    {
        return self::constant(self::IN_STOCK);
    }

    public static function outOfStock(): self
    {
        return self::constant(self::OUT_OF_STOCK);
    }

    public static function preorder(): self
    {
        return self::constant(self::PREORDER);
    }

    /**
     * @param object|string $value
     */
    public static function fromValue($value): self
    {
        $value = (string) $value;

        Assert::oneOf($value, self::getValues());

        return self::constant($value);
    }

    public static function getValues(): array
    {
        return [
            self::IN_STOCK,
            self::OUT_OF_STOCK,
            self::PREORDER,
        ];
    }
}
