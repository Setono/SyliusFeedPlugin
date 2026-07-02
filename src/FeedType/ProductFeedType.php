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
 * One row per Sylius product (§8.7), scoped over channel × locale × currency — a sibling to the
 * product_variant feed type for destinations that want product-level rows (with a "from" price and
 * an aggregated availability). Its available source fields are the registered product value
 * resolvers.
 */
final class ProductFeedType implements FeedTypeInterface
{
    /**
     * The source fields this feed type exposes, resolved from the value resolver registry by name.
     *
     * @var list<string>
     */
    private const FIELDS = [
        'id',
        'title',
        'description',
        'link',
        'main_image',
        'additional_images',
        'from_price',
        'product_availability',
        'variant_count',
        'is_configurable',
    ];

    public function __construct(
        private readonly DataSourceInterface $dataSource,
        private readonly ValueResolverRegistryInterface $valueResolverRegistry,
    ) {
    }

    public function getCode(): string
    {
        return 'product';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.feed_type.product';
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
        return [ScopeDimension::CHANNEL, ScopeDimension::LOCALE, ScopeDimension::CURRENCY];
    }

    public function getAvailableFields(): array
    {
        $fields = [];

        foreach (self::FIELDS as $name) {
            if (!$this->valueResolverRegistry->has($name)) {
                continue;
            }

            $resolver = $this->valueResolverRegistry->get($name);
            $fields[$name] = new FieldDefinition(
                $name,
                $resolver->getLabel(),
                $resolver->getType(),
                $resolver,
                'additional_images' === $name,
            );
        }

        return $fields;
    }
}
