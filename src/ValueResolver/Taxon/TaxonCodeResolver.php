<?php

declare(strict_types=1);

namespace Setono\SyliusFeedPlugin\ValueResolver\Taxon;

use Setono\SyliusFeedPlugin\Context\FeedContext;
use Setono\SyliusFeedPlugin\Mapping\FieldType;
use Setono\SyliusFeedPlugin\ValueResolver\ValueResolverInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

/**
 * The taxon's code (§8.4).
 */
final class TaxonCodeResolver implements ValueResolverInterface
{
    public function getName(): string
    {
        return 'code';
    }

    public function getLabel(): string
    {
        return 'setono_sylius_feed.value_resolver.code';
    }

    public function getType(): FieldType
    {
        return FieldType::STRING;
    }

    public function supports(string $resourceClass): bool
    {
        return is_a($resourceClass, TaxonInterface::class, true);
    }

    public function resolve(object $entity, FeedContext $context): mixed
    {
        return $entity instanceof TaxonInterface ? $entity->getCode() : null;
    }
}
