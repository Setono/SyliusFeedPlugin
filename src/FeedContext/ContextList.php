<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedContext;

use ArrayIterator;
use IteratorAggregate;

final class ContextList implements IteratorAggregate, ContextListInterface
{
    /** @var array<array-key, array|object> */
    private array $contexts = [];

    public function __construct(array $contexts = [])
    {
        foreach ($contexts as $context) {
            $this->add($context);
        }
    }

    /**
     * todo all contexts should implement an interface and be value objects
     *
     * @param array|object $context
     */
    public function add($context): void
    {
        $this->contexts[] = $context;
    }

    public function count(): int
    {
        return count($this->contexts);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->contexts);
    }
}
