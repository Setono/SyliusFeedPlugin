<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Item;

use Setono\SyliusFeedPlugin\Context\FeedContext;

/**
 * The per-item object that threads through the whole per-item flow and is the payload
 * for events, validation, and the writer (§4.2).
 *
 * The ordered output-field bag is the single source of truth and is what supports
 * arbitrary, admin-configured fields. A feed type MAY provide a typed subclass whose
 * named accessors read/write the same underlying bag.
 */
class FeedItem
{
    /** @var array<string, mixed> ordered output-field bag */
    private array $values = [];

    private bool $skipped = false;

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
}
