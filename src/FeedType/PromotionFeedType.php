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
 * One row per non-archived Sylius promotion (§8.6), scoped over channel — e.g. for a coupon/deals
 * feed destination. Its available source fields are the registered promotion value resolvers.
 *
 * The `promotion_*` field names (rather than the bare `code`/`name`/`description` used elsewhere)
 * avoid colliding with the taxon feed type's `code`/`name` and the product feed types'
 * `description` in the single, global {@see ValueResolverRegistryInterface} — the same reason the
 * product feed types distinguish `availability` from `product_availability` (§8.1, §8.7).
 */
final class PromotionFeedType implements FeedTypeInterface
{
    /**
     * @var list<string>
     */
    private const FIELDS = [
        'promotion_code',
        'promotion_name',
        'promotion_description',
        'starts_at',
        'ends_at',
        'coupon_based',
    ];

    public function __construct(
        private readonly DataSourceInterface $dataSource,
        private readonly ValueResolverRegistryInterface $valueResolverRegistry,
    ) {
    }

    public function getCode(): string
    {
        return 'promotion';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.feed_type.promotion';
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
