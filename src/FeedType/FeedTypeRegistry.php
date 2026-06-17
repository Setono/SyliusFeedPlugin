<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedType;

use Webmozart\Assert\Assert;

final class FeedTypeRegistry implements FeedTypeRegistryInterface
{
    /** @var array<string, FeedTypeInterface> */
    private array $feedTypes = [];

    /**
     * @param iterable<FeedTypeInterface> $feedTypes
     */
    public function __construct(iterable $feedTypes)
    {
        foreach ($feedTypes as $feedType) {
            $code = $feedType->getCode();
            Assert::keyNotExists($this->feedTypes, $code, sprintf('A feed type with code "%s" is already registered', $code));

            $this->feedTypes[$code] = $feedType;
        }
    }

    public function get(string $code): FeedTypeInterface
    {
        Assert::keyExists($this->feedTypes, $code, sprintf('No feed type with code "%s" is registered', $code));

        return $this->feedTypes[$code];
    }

    public function has(string $code): bool
    {
        return isset($this->feedTypes[$code]);
    }

    public function all(): array
    {
        return $this->feedTypes;
    }
}
