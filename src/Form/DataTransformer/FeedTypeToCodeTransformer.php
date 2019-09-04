<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form\DataTransformer;

use InvalidArgumentException;
use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Symfony\Component\Form\DataTransformerInterface;

final class FeedTypeToCodeTransformer implements DataTransformerInterface
{
    /** @var FeedTypeRegistryInterface */
    private $feedTypeRegistry;

    public function __construct(FeedTypeRegistryInterface $feedTypeRegistry)
    {
        $this->feedTypeRegistry = $feedTypeRegistry;
    }

    public function transform($code): FeedTypeInterface
    {
        if (!is_string($code)) {
            throw new InvalidArgumentException('Invalid type'); // todo better exception
        }

        return $this->feedTypeRegistry->get($code);
    }

    public function reverseTransform($feedType): string
    {
        if (!$feedType instanceof FeedTypeInterface) {
            throw new InvalidArgumentException('Invalid type'); // todo better exception
        }

        return $feedType->getCode();
    }
}
