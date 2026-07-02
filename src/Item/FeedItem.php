<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Item;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Webmozart\Assert\Assert;

/**
 * The per-item object that threads through the whole per-item flow and is the payload
 * for events, validation, and the writer (§4.2).
 *
 * The ordered output-field bag is the single source of truth and is what supports
 * arbitrary, admin-configured fields. A feed type MAY provide a typed subclass whose
 * named accessors read/write the same underlying bag.
 *
 * The bag is exposed as both an ordered iterator (`foreach ($item as $field => $value)`)
 * and array access (`$item['g:title']`) on top of the explicit get/set/has/remove API.
 *
 * @implements \IteratorAggregate<string, mixed>
 * @implements \ArrayAccess<string, mixed>
 */
class FeedItem implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /** @var array<string, mixed> ordered output-field bag */
    private array $values = [];

    private bool $skipped = false;

    /**
     * Resolves a named source field / value resolver (incl. `attribute:{code}`, `lookup:{table}:{column}`)
     * against this item's entity+context, on demand. Set by the generator per source so that
     * transformations, conditions and expressions can resolve source references regardless of
     * mapping order (§10 reference-resolution rule). Null until bound → source references resolve to null.
     *
     * @var (\Closure(string): mixed)|null
     */
    private ?\Closure $sourceResolver = null;

    public function __construct(
        private readonly object $entity,
        private readonly FeedContext $context,
    ) {
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getContext(): FeedContext
    {
        return $this->context;
    }

    /**
     * Bind the on-demand source resolver (see $sourceResolver).
     *
     * @param \Closure(string): mixed $sourceResolver
     */
    public function setSourceResolver(\Closure $sourceResolver): void
    {
        $this->sourceResolver = $sourceResolver;
    }

    /**
     * Resolve a named source field / value resolver against this item on demand. Returns null when
     * no resolver is bound or the name is unknown — a miss is never an error (§10).
     */
    public function resolveSource(string $field): mixed
    {
        return null === $this->sourceResolver ? null : ($this->sourceResolver)($field);
    }

    public function get(string $field): mixed
    {
        return $this->values[$field] ?? null;
    }

    public function set(string $field, mixed $value): void
    {
        $this->values[$field] = $value;
    }

    public function has(string $field): bool
    {
        return array_key_exists($field, $this->values);
    }

    public function remove(string $field): void
    {
        unset($this->values[$field]);
    }

    /**
     * @return array<string, mixed> the ordered bag
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * Veto this item: it will not be written to the feed.
     */
    public function skip(): void
    {
        $this->skipped = true;
    }

    public function isSkipped(): bool
    {
        return $this->skipped;
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        Assert::notNull($offset, 'Feed item fields must be set using a string key, not appended');

        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }

    /**
     * @return \ArrayIterator<string, mixed> the ordered bag
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->values);
    }

    public function count(): int
    {
        return count($this->values);
    }
}
