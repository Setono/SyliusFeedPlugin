<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\FeedType;

use Setono\SyliusFeedPlugin\DataSource\DataSourceInterface;
use Setono\SyliusFeedPlugin\Mapping\FieldDefinition;
use Setono\SyliusFeedPlugin\Mapping\ScopeDimension;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverRegistryInterface;

/**
 * The reference feed type (§8.1): one row per Sylius product variant, scoped over channel × locale
 * × currency. Its available source fields are the registered product value resolvers.
 */
final class ProductVariantFeedType implements FeedTypeInterface
{
    /**
     * The source fields this feed type exposes, resolved from the value resolver registry by name.
     *
     * @var list<string>
     */
    private const FIELDS = [
        'id',
        'item_group_id',
        'title',
        'description',
        'link',
        'main_image',
        'additional_images',
        'availability',
        'channel_price',
        'original_price',
        'is_configurable',
        'on_sale',
    ];

    public function __construct(
        private readonly DataSourceInterface $dataSource,
        private readonly ValueResolverRegistryInterface $valueResolverRegistry,
    ) {
    }

    public function getCode(): string
    {
        return 'product_variant';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.feed_type.product_variant';
    }

    public function getDataSource(): DataSourceInterface
    {
        return $this->dataSource;
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
