<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Product;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Item\Google\Availability;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;

/**
 * Maps the variant's inventory to an availability value (§8.1, §9.7): an untracked variant is
 * always in stock; a tracked one is in stock when its available quantity (on-hand minus on-hold)
 * is positive.
 */
final class AvailabilityResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'availability';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.availability';
    }

    public function getType(): FieldType
    {
        return FieldType::STRING;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, ProductVariantInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        if (!$entity instanceof ProductVariantInterface) {
            return null;
        }

        if (!$entity->isTracked()) {
            return Availability::IN_STOCK->value;
        }

        $available = (int) $entity->getOnHand() - (int) $entity->getOnHold();

        return $available > 0 ? Availability::IN_STOCK->value : Availability::OUT_OF_STOCK->value;
    }
}
