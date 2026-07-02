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
 * One row per accepted Sylius product review (§8.5), scoped over channel × locale — e.g. for a
 * reviews-feed destination such as Google's product ratings feed. Its available source fields are
 * the registered product review value resolvers.
 */
final class ProductReviewFeedType implements FeedTypeInterface
{
    /**
     * @var list<string>
     */
    private const FIELDS = [
        'rating',
        'comment',
        'author',
        'product_id',
        'review_created_at',
        'status',
    ];

    public function __construct(
        private readonly DataSourceInterface $dataSource,
        private readonly ValueResolverRegistryInterface $valueResolverRegistry,
    ) {
    }

    public function getCode(): string
    {
        return 'product_review';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.feed_type.product_review';
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
        return [ScopeDimension::CHANNEL, ScopeDimension::LOCALE];
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
