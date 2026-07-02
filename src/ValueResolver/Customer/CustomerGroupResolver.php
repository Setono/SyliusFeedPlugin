<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Customer;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Customer\Model\CustomerInterface;

/**
 * The code of the customer's group, or null when the customer has no group (§8.3).
 */
final class CustomerGroupResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'group';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.group';
    }

    public function getType(): FieldType
    {
        return FieldType::STRING;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, CustomerInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        return $entity instanceof CustomerInterface ? $entity->getGroup()?->getCode() : null;
    }
}
