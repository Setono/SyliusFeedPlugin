<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedType;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\DataSource\DataSourceInterface;
use Setono\SyliusFeedPlugin\Item\FeedItem;
use Setono\SyliusFeedPlugin\Mapping\FieldDefinition;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverRegistryInterface;

/**
 * One row per Sylius customer (§8.3). Declares no scope dimensions at all — a customer export is
 * not channel-, locale- or currency-specific — proving the engine works just as well with zero
 * scope fan-out as it does with three (§6.2, §8.3). Its available source fields are the registered
 * customer value resolvers.
 */
final class CustomerFeedType implements FeedTypeInterface
{
    /**
     * @var list<string>
     */
    private const FIELDS = [
        'email',
        'first_name',
        'last_name',
        'group',
        'subscribed_to_newsletter',
        'created_at',
    ];

    public function __construct(
        private readonly DataSourceInterface $dataSource,
        private readonly ValueResolverRegistryInterface $valueResolverRegistry,
    ) {
    }

    public function getCode(): string
    {
        return 'customer';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.feed_type.customer';
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
        return [];
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
