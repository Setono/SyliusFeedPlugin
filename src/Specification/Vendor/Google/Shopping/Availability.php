<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Specification\Vendor\Google\Shopping;

enum Availability: string
{
    case InStock = 'in_stock';
    case OutOfStock = 'out_of_stock';
    case Preorder = 'preorder';
    case Backorder = 'backorder';
}
