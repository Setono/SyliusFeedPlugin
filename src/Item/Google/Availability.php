<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Item\Google;

/**
 * The Google Shopping `availability` values (§8.1).
 */
enum Availability: string
{
    case IN_STOCK = 'in_stock';
    case OUT_OF_STOCK = 'out_of_stock';
    case PREORDER = 'preorder';
    case BACKORDER = 'backorder';
}
