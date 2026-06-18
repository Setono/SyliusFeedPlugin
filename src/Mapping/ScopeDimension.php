<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Mapping;

/**
 * A dimension a feed type fans out over when generating contexts (§6.2).
 */
enum ScopeDimension: string
{
    case CHANNEL = 'channel';
    case LOCALE = 'locale';
    case CURRENCY = 'currency';
}
