<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Registry;

use Webmozart\Assert\Assert;

/**
 * Base for the plugin's tagged-service registries: builds a code-keyed map from a tagged
 * iterator, guards against duplicate keys, and provides the shared iterable/countable lookup
 * behaviour. Concrete registries only supply the key extractor and a type-narrowed `get()`.
 *
 * @template T of object
 * @implements \IteratorAggregate<string, T>
 */
abstract class Registry implements \IteratorAggregate, \Countable
{
    /** @var array<string, T> */
    private array $items = [];

    /**
     * @param iterable<T> $items
     */
    public function __construct(iterable $items)
    {
        foreach ($items as $item) {
            $key = $this->getKey($item);
            Assert::keyNotExists(
                $this->items,
                $key,
                sprintf('An item with key "%s" is already registered in %s', $key, static::class),
            );

            $this->items[$key] = $item;
        }
    }

    public function has(string $key): bool
    {
        return isset($this->items[$key]);
    }

    /**
     * @return array<string, T>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return \ArrayIterator<string, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @param T $item
     */
    abstract protected function getKey(object $item): string;

    /**
     * @return T
     */
    protected function getByKey(string $key): object
    {
        Assert::keyExists(
            $this->items,
            $key,
            sprintf('No item with key "%s" is registered in %s', $key, static::class),
        );

        return $this->items[$key];
    }
}
