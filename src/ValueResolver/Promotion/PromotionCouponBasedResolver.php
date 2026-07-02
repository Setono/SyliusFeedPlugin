<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Promotion;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Promotion\Model\PromotionInterface;

/**
 * Whether the promotion requires a coupon to be applied (§8.6).
 */
final class PromotionCouponBasedResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'coupon_based';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.coupon_based';
    }

    public function getType(): FieldType
    {
        return FieldType::BOOL;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, PromotionInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        return $entity instanceof PromotionInterface ? $entity->isCouponBased() : null;
    }
}
