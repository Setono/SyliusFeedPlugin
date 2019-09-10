<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedContext;

use ArrayIterator;
use IteratorAggregate;

final class ContextList implements IteratorAggregate, ContextListInterface
{
    /**
     * @var array
     */
    private $contexts = [];

    public function __construct(array $contexts = [])
    {
        foreach ($contexts as $context) {
            $this->add($context);
        }
    }

    /**
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
