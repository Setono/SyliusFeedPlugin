<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping;

use Setono\SyliusFeedPlugin\Feed\Model\Enum;

final class Condition extends Enum
{
    private const NEW = 'new';

    private const REFURBISHED = 'refurbished';

    private const USED = 'used';

    public static function new(): self
    {
        return self::constant(self::NEW);
    }

    public static function refurbished(): self
    {
        return self::constant(self::REFURBISHED);
    }

    public static function used(): self
    {
        return self::constant(self::USED);
    }

    public static function getValues(): array
    {
        return [
            self::NEW,
            self::REFURBISHED,
            self::USED,
        ];
    }
}
