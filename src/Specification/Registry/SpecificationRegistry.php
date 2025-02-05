<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Specification\Registry;

use Setono\SyliusFeedPlugin\Specification\Specification;

final class SpecificationRegistry implements SpecificationRegistryInterface
{
    /** @var list<class-string<Specification>> */
    private array $specifications = [];

    public function add(string $specification): void
    {
        if ($this->has($specification)) {
            throw new \InvalidArgumentException(sprintf('The specification "%s" already exists', $specification));
        }

        if (!is_a($specification, Specification::class, true)) {
            throw new \InvalidArgumentException(sprintf('The specification "%s" is not a valid specification. It must be an instance of %s', $specification, Specification::class));
        }

        $this->specifications[] = $specification;
    }

    /**
     * @psalm-assert-if-true class-string<Specification> $this->specifications[$specification]
     */
    public function has(string $specification): bool
    {
        return in_array($specification, $this->specifications, true);
    }

    public function all(): array
    {
        return $this->specifications;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->specifications);
    }

    public function count(): int
    {
        return count($this->specifications);
    }
}
