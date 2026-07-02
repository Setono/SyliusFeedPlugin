<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Order;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * The email address of the order's customer, or null for an order with no customer attached
 * (§8.2).
 */
final class OrderCustomerEmailResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'customer_email';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.customer_email';
    }

    public function getType(): FieldType
    {
        return FieldType::STRING;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, OrderInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        return $entity instanceof OrderInterface ? $entity->getCustomer()?->getEmail() : null;
    }
}
