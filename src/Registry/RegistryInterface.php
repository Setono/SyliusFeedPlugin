<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Registry;

/**
 * The contract shared by every extension-point registry: a code-keyed, iterable, countable
 * collection of tagged services.
 *
 * @template T of object
 * @extends \IteratorAggregate<string, T>
 */
interface RegistryInterface extends \IteratorAggregate, \Countable
{
    /**
     * @return T
     */
    public function get(string $key): object;

    public function has(string $key): bool;

    /**
     * @return array<string, T>
     */
    public function all(): array;
}
