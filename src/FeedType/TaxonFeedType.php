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
 * One row per Sylius taxon (§8.4), scoped over locale only — a taxonomy/category feed for
 * destinations that mirror a shop's navigation (e.g. a "product_type"/category sitemap feed).
 * Its available source fields are the registered taxon value resolvers.
 */
final class TaxonFeedType implements FeedTypeInterface
{
    /**
     * @var list<string>
     */
    private const FIELDS = [
        'code',
        'name',
        'slug',
        'parent_code',
        'position',
    ];

    public function __construct(
        private readonly DataSourceInterface $dataSource,
        private readonly ValueResolverRegistryInterface $valueResolverRegistry,
    ) {
    }

    public function getCode(): string
    {
        return 'taxon';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.feed_type.taxon';
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
        return [ScopeDimension::LOCALE];
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
