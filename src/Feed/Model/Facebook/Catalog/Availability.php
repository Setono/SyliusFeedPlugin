<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Feed\Model\Facebook\Catalog;

use Spatie\Enum\Enum;

/**
 * in stock, out of stock, available for order, discontinued
 * @method static self inStock()
 * @method static self outOfStock()
 * @method static self availableForOrder
 * @method static self discontinued()
 */
final class Availability extends Enum
{
    /**
     * @return array<string, string>
     */
    protected static function values(): array
    {
        return [
            'inStock' => 'in stock',
            'outOfStock' => 'out of stock',
            'availableForOrder' => ' available for order',
            'discontinued' => 'discontinued',
        ];
    }
}
