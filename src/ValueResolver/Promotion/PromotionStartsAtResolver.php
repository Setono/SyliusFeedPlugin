<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Promotion;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Promotion\Model\PromotionInterface;

/**
 * The moment the promotion starts, or null when it has no start date (§8.6). Returns the raw
 * `\DateTimeInterface` — a mapping applies the `date_format` transformation to render it as a
 * string (§10).
 */
final class PromotionStartsAtResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'starts_at';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.starts_at';
    }

    public function getType(): FieldType
    {
        return FieldType::DATE;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, PromotionInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        return $entity instanceof PromotionInterface ? $entity->getStartsAt() : null;
    }
}
