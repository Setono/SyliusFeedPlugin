<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Reference;

use Setono\SyliusFeedPlugin\Item\FeedItem;

/**
 * Resolves a single "reference" — the one grammar shared by `concat`, `conditional`,
 * `FeedField.condition`, `FeedFilter` and the `fields[…]` map in expressions (§10, normative).
 *
 * A reference is one of, resolved in this order:
 *  1. `value`            — the value currently flowing through this field's chain,
 *  2. a quoted literal   — `'foo'` / `"foo"` (the inner text, verbatim),
 *  3. an output-field key — a bag key mapped **earlier** (lower position),
 *  4. a source field / resolver name — incl. `attribute:{code}` / `lookup:{table}:{column}`,
 *     resolved on demand, so it is available regardless of mapping order,
 *  5. otherwise `null`.
 *
 * Source/output references are order-independent for sources but a not-yet-mapped output key
 * falls through to source resolution or null — never an error.
 */
interface ReferenceResolverInterface
{
    public function resolve(string $reference, mixed $currentValue, FeedItem $item): mixed;
}
