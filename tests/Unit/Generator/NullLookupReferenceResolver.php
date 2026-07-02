<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\Tests\Unit\Generator;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Lookup\LookupReferenceResolverInterface;

/**
 * A no-op lookup reference resolver for generator tests that don't exercise `lookup:` references.
 */
final class NullLookupReferenceResolver implements LookupReferenceResolverInterface
{
    public function supports(string $reference): bool
    {
        return false;
    }

    public function resolve(string $reference, object $entity, FeedContext $context, array $availableFields): mixed
    {
        return null;
    }
}
