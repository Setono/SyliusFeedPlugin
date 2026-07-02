<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\ProductReview;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Review\Model\ReviewInterface;

/**
 * The code of the product being reviewed, or null when the review's subject is not a product
 * (§8.5).
 */
final class ReviewProductIdResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'product_id';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.product_id';
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
        if (!$entity instanceof ReviewInterface) {
            return null;
        }

        $subject = $entity->getReviewSubject();

        return $subject instanceof ProductInterface ? $subject->getCode() : null;
    }
}
