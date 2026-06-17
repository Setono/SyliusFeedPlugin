<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Filter;

use Webmozart\Assert\Assert;

final class FilterOperatorRegistry implements FilterOperatorRegistryInterface
{
    /** @var array<string, FilterOperatorInterface> */
    private array $operators = [];

    /**
     * @param iterable<FilterOperatorInterface> $operators
     */
    public function __construct(iterable $operators)
    {
        foreach ($operators as $operator) {
            $name = $operator->getName();
            Assert::keyNotExists($this->operators, $name, sprintf('A filter operator with name "%s" is already registered', $name));

            $this->operators[$name] = $operator;
        }
    }

    public function get(string $name): FilterOperatorInterface
    {
        Assert::keyExists($this->operators, $name, sprintf('No filter operator with name "%s" is registered', $name));

        return $this->operators[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->operators[$name]);
    }

    public function all(): array
    {
        return $this->operators;
    }
}
