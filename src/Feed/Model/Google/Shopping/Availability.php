<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Feed\Model\Google\Shopping;

use Spatie\Enum\Enum;

/**
 * @method static self backOrder()
 * @method static self inStock()
 * @method static self outOfStock()
 * @method static self preOrder()
 */
final class Availability extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function values(): array
    {
        return [
            'backOrder' => 'backorder',
            'inStock' => 'in_stock',
            'outOfStock' => 'out_of_stock',
            'preOrder' => 'preorder',
        ];
    }
}
