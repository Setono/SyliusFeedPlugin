<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedType;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\DataSource\DataSourceInterface;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\FieldDefinition;
use Setono\SyliusFeedPlugin\Mapping\ScopeDimension;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverRegistryInterface;

/**
 * One row per completed Sylius order (§8.2), scoped over channel — the reference "non-catalog"
 * feed type: a CSV order export driven through the very same engine as the product feeds. Its
 * available source fields are the registered order value resolvers.
 */
final class OrderFeedType implements FeedTypeInterface
{
    /**
     * @var list<string>
     */
    private const FIELDS = [
        'number',
        'total',
        'currency_code',
        'customer_email',
        'state',
        'checkout_completed_at',
    ];

    public function __construct(
        private readonly DataSourceInterface $dataSource,
        private readonly ValueResolverRegistryInterface $valueResolverRegistry,
    ) {
    }

    public function getCode(): string
    {
        return 'order';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.feed_type.order';
    }

    public function getDataSource(): DataSourceInterface
    {
        return $this->dataSource;
    }

    public function createItem(object $entity, FeedContext $context): FeedItem
    {
        return new FeedItem($entity, $context);
    }

    public function getScopeDimensions(): array
    {
        return [ScopeDimension::CHANNEL];
    }

    public function getAvailableFields(): array
    {
        $fields = [];

        foreach (self::FIELDS as $name) {
            if (!$this->valueResolverRegistry->has($name)) {
                continue;
            }

            $resolver = $this->valueResolverRegistry->get($name);
            $fields[$name] = new FieldDefinition($name, $resolver->getLabel(), $resolver->getType(), $resolver);
        }

        return $fields;
    }
}
