<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver;

use Webmozart\Assert\Assert;

final class ValueResolverRegistry implements ValueResolverRegistryInterface
{
    /** @var array<string, ValueResolverInterface> */
    private array $resolvers = [];

    /**
     * @param iterable<ValueResolverInterface> $resolvers
     */
    public function __construct(iterable $resolvers)
    {
        foreach ($resolvers as $resolver) {
            $name = $resolver->getName();
            Assert::keyNotExists($this->resolvers, $name, sprintf('A value resolver with name "%s" is already registered', $name));

            $this->resolvers[$name] = $resolver;
        }
    }

    public function get(string $name): ValueResolverInterface
    {
        Assert::keyExists($this->resolvers, $name, sprintf('No value resolver with name "%s" is registered', $name));

        return $this->resolvers[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->resolvers[$name]);
    }

    public function all(): array
    {
        return $this->resolvers;
    }
}
