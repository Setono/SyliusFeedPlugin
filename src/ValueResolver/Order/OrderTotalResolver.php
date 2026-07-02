<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Order;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * The order's total, in minor units, in the order's own currency (§8.2, §9.1). Unlike the product
 * feed types' money fields, this is not FX-converted to a context currency — the order feed type
 * declares no currency scope dimension, so the order's own {@see OrderInterface::getCurrencyCode()}
 * (exposed as the sibling `currency_code` field) is the value's unit of record.
 */
final class OrderTotalResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'total';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.total';
    }

    public function getType(): FieldType
    {
        return FieldType::MONEY;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, OrderInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        return $entity instanceof OrderInterface ? $entity->getTotal() : null;
    }
}
