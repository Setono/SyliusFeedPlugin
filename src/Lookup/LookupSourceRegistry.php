<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Lookup;

use Webmozart\Assert\Assert;

final class LookupSourceRegistry implements LookupSourceRegistryInterface
{
    /** @var array<string, LookupSourceInterface> */
    private array $sources = [];

    /**
     * @param iterable<LookupSourceInterface> $sources
     */
    public function __construct(iterable $sources)
    {
        foreach ($sources as $source) {
            $type = $source->getType();
            Assert::keyNotExists($this->sources, $type, sprintf('A lookup source with type "%s" is already registered', $type));

            $this->sources[$type] = $source;
        }
    }

    public function get(string $type): LookupSourceInterface
    {
        Assert::keyExists($this->sources, $type, sprintf('No lookup source with type "%s" is registered', $type));

        return $this->sources[$type];
    }

    public function has(string $type): bool
    {
        return isset($this->sources[$type]);
    }

    public function all(): array
    {
        return $this->sources;
    }
}
