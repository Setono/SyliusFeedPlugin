<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * A feed may only be enabled once every mapped field that `requiresInput` has been resolved by the
 * admin (§7, §10): a preset flags fields it cannot derive from core Sylius (brand, gtin,
 * google_product_category) so the feed cannot ship half-mapped.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class FeedResolvable extends Constraint
{
    public string $message = 'setono_sylius_feed.feed.resolve_required_fields_before_enabling';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
