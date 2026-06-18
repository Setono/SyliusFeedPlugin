<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Item\Google;

/**
 * The Google Shopping `condition` values (§8.1).
 */
enum Condition: string
{
    case NEW = 'new';
    case REFURBISHED = 'refurbished';
    case USED = 'used';
}
