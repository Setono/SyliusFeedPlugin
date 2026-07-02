<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Reference;

use Setono\SyliusFeedPlugin\Item\FeedItem;

/**
 * The single reference-resolution rule (§10). See {@see ReferenceResolverInterface}.
 */
final class ReferenceResolver implements ReferenceResolverInterface
{
    public function resolve(string $reference, mixed $currentValue, FeedItem $item): mixed
    {
        if ('value' === $reference) {
            return $currentValue;
        }

        $literal = $this->unquote($reference);
        if (null !== $literal) {
            return $literal;
        }

        // An output-field key mapped earlier wins over source resolution.
        if ($item->has($reference)) {
            return $item->get($reference);
        }

        // Source field / resolver (incl. attribute:/lookup:), resolved on demand; null on a miss.
        return $item->resolveSource($reference);
    }

    /**
     * Returns the inner text of a quoted literal (`'foo'` / `"foo"`), or null when $reference is
     * not a quoted literal.
     */
    private function unquote(string $reference): ?string
    {
        if (mb_strlen($reference) < 2) {
            return null;
        }

        $first = mb_substr($reference, 0, 1);
        $last = mb_substr($reference, -1);

        if (("'" === $first && "'" === $last) || ('"' === $first && '"' === $last)) {
            return mb_substr($reference, 1, -1);
        }

        return null;
    }
}
