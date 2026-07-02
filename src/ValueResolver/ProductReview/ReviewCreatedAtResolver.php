<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\ProductReview;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Review\Model\ReviewInterface;

/**
 * The moment the review was created (§8.5). Returns the raw `\DateTimeInterface` — a mapping
 * applies the `date_format` transformation to render it as a string (§10).
 *
 * Named `review_created_at` (rather than the bare `created_at` used by the customer feed type) to
 * avoid colliding with {@see \Setono\SyliusFeedPlugin\ValueResolver\Customer\CustomerCreatedAtResolver}
 * in the single, global value resolver registry (§8.3, §8.5).
 */
final class ReviewCreatedAtResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'review_created_at';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.review_created_at';
    }

    public function getType(): FieldType
    {
        return FieldType::DATE;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, ReviewInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        return $entity instanceof ReviewInterface ? $entity->getCreatedAt() : null;
    }
}
