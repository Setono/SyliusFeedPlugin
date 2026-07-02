<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Order;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * The moment the order's checkout was completed, or null if it never was (§8.2). Returns the raw
 * `\DateTimeInterface` — a mapping applies the `date_format` transformation to render it as a
 * string (§10).
 */
final class OrderCheckoutCompletedAtResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'checkout_completed_at';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.checkout_completed_at';
    }

    public function getType(): FieldType
    {
        return FieldType::DATE;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, OrderInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        return $entity instanceof OrderInterface ? $entity->getCheckoutCompletedAt() : null;
    }
}
