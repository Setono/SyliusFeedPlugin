<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\ProductReview;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Review\Model\ReviewInterface;

/**
 * The review's moderation status, e.g. `new`, `accepted`, `rejected` (§8.5).
 */
final class ReviewStatusResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'status';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.status';
    }

    public function getType(): FieldType
    {
        return FieldType::STRING;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, ReviewInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        return $entity instanceof ReviewInterface ? $entity->getStatus() : null;
    }
}
