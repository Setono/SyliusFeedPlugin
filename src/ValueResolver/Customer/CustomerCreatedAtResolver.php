<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Customer;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Customer\Model\CustomerInterface;

/**
 * The moment the customer was created (§8.3). Returns the raw `\DateTimeInterface` — a mapping
 * applies the `date_format` transformation to render it as a string (§10).
 */
final class CustomerCreatedAtResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'created_at';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.created_at';
    }

    public function getType(): FieldType
    {
        return FieldType::DATE;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, CustomerInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        return $entity instanceof CustomerInterface ? $entity->getCreatedAt() : null;
    }
}
