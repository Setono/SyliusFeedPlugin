<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Form\DataTransformer;

use Setono\SyliusFeedPlugin\FeedType\FeedTypeInterface;
use Setono\SyliusFeedPlugin\Registry\FeedTypeRegistryInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

final class FeedTypeToCodeTransformer implements DataTransformerInterface
{
    /** @var FeedTypeRegistryInterface */
    private $feedTypeRegistry;

    public function __construct(FeedTypeRegistryInterface $feedTypeRegistry)
    {
        $this->feedTypeRegistry = $feedTypeRegistry;
    }

    public function transform($code): ?FeedTypeInterface
    {
        if (null === $code || '' === $code) {
            return null;
        }

        if (!is_string($code)) {
            throw new UnexpectedTypeException($code, 'string');
        }

        return $this->feedTypeRegistry->get($code);
    }

    public function reverseTransform($feedType): ?string
    {
        if (null === $feedType) {
            return null;
        }

        if (!$feedType instanceof FeedTypeInterface) {
            throw new UnexpectedTypeException($feedType, FeedTypeInterface::class);
        }

        return $feedType->getCode();
    }
}
