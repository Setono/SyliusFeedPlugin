<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\ProductReview;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Review\Model\ReviewerInterface;
use Sylius\Component\Review\Model\ReviewInterface;

/**
 * The review author's display name — first + last name when set, falling back to the author's
 * email — or null when the review has no author (§8.5).
 */
final class ReviewAuthorResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'author';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.author';
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

        $author = $entity->getAuthor();
        if (!$author instanceof ReviewerInterface) {
            return null;
        }

        $name = trim(sprintf('%s %s', (string) $author->getFirstName(), (string) $author->getLastName()));

        return '' !== $name ? $name : $author->getEmail();
    }
}
