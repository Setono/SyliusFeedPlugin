<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Message;

use Closure;

/**
 * @internal
 *
 * @template T
 */
final class Buffer
{
    private int $count = 0;

    /** @var list<T> */
    private array $buffer = [];

    /**
     * @param Closure(list<T>):void $callback
     */
    public function __construct(
        private readonly int $bufferSize,
        private readonly Closure $callback,
    ) {
    }

    /**
     * @param T $item
     */
    public function push(mixed $item): void
    {
        $this->buffer[] = $item;
        ++$this->count;

        if ($this->count >= $this->bufferSize) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        if ($this->count > 0) {
            ($this->callback)($this->buffer);
            $this->buffer = [];
            $this->count = 0;
        }
    }
}
