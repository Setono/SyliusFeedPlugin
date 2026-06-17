<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Transformation;

use Webmozart\Assert\Assert;

final class TransformationRegistry implements TransformationRegistryInterface
{
    /** @var array<string, TransformationInterface> */
    private array $transformations = [];

    /**
     * @param iterable<TransformationInterface> $transformations
     */
    public function __construct(iterable $transformations)
    {
        foreach ($transformations as $transformation) {
            $type = $transformation->getType();
            Assert::keyNotExists($this->transformations, $type, sprintf('A transformation with type "%s" is already registered', $type));

            $this->transformations[$type] = $transformation;
        }
    }

    public function get(string $type): TransformationInterface
    {
        Assert::keyExists($this->transformations, $type, sprintf('No transformation with type "%s" is registered', $type));

        return $this->transformations[$type];
    }

    public function has(string $type): bool
    {
        return isset($this->transformations[$type]);
    }

    public function all(): array
    {
        return $this->transformations;
    }
}
