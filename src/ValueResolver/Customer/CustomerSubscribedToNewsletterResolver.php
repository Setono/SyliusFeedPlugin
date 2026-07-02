<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Customer;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Customer\Model\CustomerInterface;

/**
 * Whether the customer is subscribed to the newsletter (§8.3).
 */
final class CustomerSubscribedToNewsletterResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'subscribed_to_newsletter';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.subscribed_to_newsletter';
    }

    public function getType(): FieldType
    {
        return FieldType::BOOL;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, CustomerInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        return $entity instanceof CustomerInterface ? $entity->isSubscribedToNewsletter() : null;
    }
}
