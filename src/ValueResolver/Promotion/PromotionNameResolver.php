<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Promotion;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Promotion\Model\PromotionInterface;

/**
 * The promotion's name (§8.6).
 *
 * Named `promotion_name` — see {@see \Setono\SyliusFeedPlugin\FeedType\PromotionFeedType} for why
 * the promotion fields are prefixed.
 */
final class PromotionNameResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'promotion_name';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.promotion_name';
    }

    public function getType(): FieldType
    {
        return FieldType::STRING;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, PromotionInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        return $entity instanceof PromotionInterface ? $entity->getName() : null;
    }
}
